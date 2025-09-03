<?php

namespace App\Http\Controllers;

use App\Models\ConsultationBooking;
use Illuminate\Http\Request;

class HostConsultationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function myAssigned()
    {
        $hostId = auth()->id();
        $bookings = ConsultationBooking::query()
            ->assignedToHost($hostId)
            ->with(['user:id,name,email,mobile,phone_number', 'meeting:id,uuid'])
            ->orderByDesc('created_at')
            ->paginate((int) request('per_page', 15));

        return $this->ok($bookings);
    }

    public function show(int $id)
    {
        $hostId = auth()->id();
        $booking = ConsultationBooking::query()
            ->assignedToHost($hostId)
            ->with(['user:id,name,email,mobile,phone_number', 'meeting:id,uuid'])
            ->findOrFail($id);

        return $this->ok($booking);
    }

    public function destroy(int $id)
    {
        $hostId = auth()->id();
        $booking = ConsultationBooking::query()->assignedToHost($hostId)->findOrFail($id);
        if (in_array($booking->status, ['scheduled', 'completed'])) {
            return $this->error(['message' => 'Cannot delete a scheduled or completed booking'], 422);
        }
        $booking->delete();
        return $this->success(['message' => 'Booking deleted']);
    }

    public function linkMeeting(Request $request, int $id)
    {
        $hostId = auth()->id();
        $data = $request->validate([
            'meeting' => 'required|string', // uuid or numeric id
        ]);

        $booking = ConsultationBooking::query()
            ->assignedToHost($hostId)
            ->findOrFail($id);

        // Find meeting by uuid or id
        $meeting = \App\Models\Meeting::query()
            ->where('uuid', $data['meeting'])
            ->orWhere('id', $data['meeting'])
            ->firstOrFail();

        $booking->meeting_id = $meeting->id;
        if ($booking->status === 'assigned') {
            $booking->status = 'scheduled';
        }
        $booking->save();
        $booking->loadMissing('meeting:id,uuid');

        return $this->success(['message' => 'Linked to meeting', 'data' => $booking]);
    }
}


