<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationBooking;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\AdminAnnouncement;
use App\Notifications\HostAssignedConsultation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConsultationAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = ConsultationBooking::query()
            ->with(['user:id,name,email', 'host:id,name,email'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->get('status')))
            ->orderByDesc('created_at');
        return $this->ok($query->paginate((int)$request->get('per_page', 15)));
    }

    public function assign(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'host_id' => 'required|exists:users,id',
        ]);
        $booking = ConsultationBooking::findOrFail($id);
        // Guard: never touch preferred_datetime here
        $originalPreferred = $booking->preferred_datetime;
        // Do NOT change preferred_datetime on assignment
        $booking->assigned_host_id = $data['host_id'];
        $booking->status = 'assigned';
        $booking->save();
        // Restore preferred_datetime if DB-level triggers or defaults changed it
        if ($originalPreferred && $booking->preferred_datetime != $originalPreferred) {
            $booking->preferred_datetime = $originalPreferred;
            $booking->save();
        }

        // Ensure assigned state does not alter preferred_datetime; echo it back explicitly
        $booking->refresh();

        // Notify assigned host with booking details
        $host = User::find($data['host_id']);
        $host->notify(new HostAssignedConsultation($booking->id, $booking->user_id));

        return $this->success([
            'message' => 'Host assigned',
            'data' => $booking,
        ]);
    }

    public function schedule(Request $request, int $id): JsonResponse
    {
        // Accept multiple keys: start_date_time, startDateTime, startTime, start_time, start
        $all = $request->all();
        $all['start_date_time'] = $all['start_date_time']
            ?? $all['startDateTime']
            ?? $all['startTime']
            ?? $all['start_time']
            ?? $all['start']
            ?? null;

        // parse common non-ISO to ISO
        $start = $this->parseFlexibleDateTime($all['start_date_time'] ?? null);

        // Optional: allow overriding duration via payload
        $duration = null;
        if (isset($all['duration'])) {
            $duration = (int) $all['duration'];
        } else if (isset($all['duration_minutes'])) {
            $duration = (int) $all['duration_minutes'];
        } else if (isset($all['durationMinutes'])) {
            $duration = (int) $all['durationMinutes'];
        }
        if ($duration !== null && $duration < 15) {
            return $this->error(['message' => 'Duration must be at least 15 minutes'], 422);
        }

        $booking = ConsultationBooking::findOrFail($id);
        $data = [
            // If admin didn't provide a start, use user's preferred time
            'start_date_time' => $start ?: ($booking->preferred_datetime ? Carbon::parse($booking->preferred_datetime)->toIso8601String() : null),
            'duration_minutes' => $duration,
        ];
        if (!$booking->assigned_host_id) {
            return $this->error(['message' => 'Assign a host first'], 422);
        }

        // Create meeting via repository would be better; for brevity, create directly
        $meeting = Meeting::create([
            'user_id' => $booking->assigned_host_id,
            'title' => 'Consultation - '.$booking->reason_key,
            'agenda' => $booking->notes,
            'description' => 'User consultation',
            // Model mutator will store in UTC
            'start_date_time' => $data['start_date_time'] ?? Carbon::parse($booking->preferred_datetime)->toIso8601String(),
            'period' => $data['duration_minutes'] ?? $booking->duration_minutes,
        ]);

        $booking->meeting_id = $meeting->id;
        $booking->status = 'scheduled';
        $booking->save();
        // Eager load meeting to expose meeting_uuid on serialized booking
        $booking->loadMissing('meeting:id,uuid');

        // Notify host and patient with full meeting details
        try {
            $hostUser = User::find($booking->assigned_host_id);
            $patientUser = User::find($booking->user_id);
            if (class_exists('App\\Notifications\\MeetingScheduled')) {
                $notification = new \App\Notifications\MeetingScheduled($meeting);
                if ($hostUser) { $hostUser->notify($notification); }
                if ($patientUser) { $patientUser->notify($notification); }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send meeting notifications', ['error' => $e->getMessage()]);
        }

        return $this->success(['message' => 'Meeting scheduled', 'data' => $booking]);
    }

    private function parseFlexibleDateTime(?string $value): ?string
    {
        if (!$value) return null;
        $candidate = trim($value);
        if ($candidate === '') return null;

        if (preg_match('/^(\d{1,2}\/\d{1,2}):(\d{4})(.*)$/', $candidate, $m)) {
            $candidate = str_replace($m[1] . ':' . $m[2], $m[1] . '/' . $m[2], $candidate);
        }

        $formats = [
            'm/d/Y h:i A', // e.g., 08/30/2025 09:40 AM
            'm/d/Y H:i',   // e.g., 08/30/2025 09:40
            'd/m/Y h:i A',
            'd/m/Y H:i',
            'Y-m-d\\TH:i:sP',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
        ];

        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $candidate);
                if ($dt !== false) {
                    return $dt->format('Y-m-d H:i:s');
                }
            } catch (\Throwable $e) {
            }
        }
        try {
            return Carbon::parse($candidate)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,assigned,scheduled,completed,cancelled',
        ]);
        $booking = ConsultationBooking::findOrFail($id);
        $booking->status = $data['status'];
        $booking->save();
        return $this->success(['message' => 'Status updated', 'data' => $booking]);
    }

    public function destroy(int $id): JsonResponse
    {
        $booking = ConsultationBooking::findOrFail($id);
        if (in_array($booking->status, ['scheduled', 'completed'])) {
            return $this->error(['message' => 'Cannot delete a scheduled or completed booking'], 422);
        }
        $booking->delete();
        return $this->success(['message' => 'Booking deleted']);
    }
}
