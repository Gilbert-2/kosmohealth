<?php

namespace App\Http\Controllers;

use App\Models\ConsultationBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\ConsultationSettingsRepository;

class UserConsultationBookingController extends Controller
{
    private ConsultationSettingsRepository $settings;

    public function __construct(ConsultationSettingsRepository $settings)
    {
        $this->settings = $settings;
    }
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $bookings = ConsultationBooking::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 15));
        return $this->ok($bookings);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'reason_key' => 'required|string',
            'preferred_datetime' => 'required|date',
            'duration_minutes' => 'required|integer|min:15|max:180',
            'notes' => 'nullable|string|max:2000',
        ]);

        $settings = $this->settings->get();
        $reasons = collect($settings['reasons'] ?? []);
        $reason = $reasons->firstWhere('key', $data['reason_key']);
        if (! $reason) {
            return $this->error(['message' => 'Invalid reason selected'], 422);
        }

        $allowedDurations = collect($reason['allowed_durations'] ?? []);
        if ($allowedDurations->isNotEmpty() && ! $allowedDurations->contains((int) $data['duration_minutes'])) {
            return $this->error(['message' => 'Duration not allowed for selected reason'], 422);
        }

        $allowedDatetimes = collect($reason['allowed_datetimes'] ?? []);
        if ($allowedDatetimes->isNotEmpty()) {
            $preferred = \Carbon\Carbon::parse($data['preferred_datetime']);
            $match = $allowedDatetimes->first(function ($slot) use ($preferred) {
                try {
                    $slotTime = \Carbon\Carbon::parse($slot);
                    return $slotTime->equalTo($preferred);
                } catch (\Throwable $e) {
                    return false;
                }
            });
            if (! $match) {
                return $this->error(['message' => 'Selected date/time not allowed for selected reason'], 422);
            }
        }

        // Normalize preferred_datetime to UTC using model mutator
        $booking = ConsultationBooking::create([
            'user_id' => $user->id,
            'reason_key' => $data['reason_key'],
            'preferred_datetime' => $data['preferred_datetime'],
            'duration_minutes' => $data['duration_minutes'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        // Re-read to ensure preferred_datetime persisted correctly without auto-update
        $booking->refresh();

        return $this->success(['message' => 'Consultation request submitted', 'data' => $booking]);
    }

    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        $booking = ConsultationBooking::where('user_id', $user->id)->findOrFail($id);
        return $this->ok($booking);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        $booking = ConsultationBooking::where('user_id', $user->id)->findOrFail($id);
        // Prevent deleting after scheduled/completed
        if (in_array($booking->status, ['scheduled', 'completed'])) {
            return $this->error(['message' => 'Cannot delete a scheduled or completed booking'], 422);
        }
        $booking->delete();
        return $this->success(['message' => 'Booking deleted']);
    }
}
