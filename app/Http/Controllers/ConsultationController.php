<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Http\Resources\Meeting as MeetingResource;
use App\Repositories\MeetingRepository;
use Illuminate\Http\Request;
use App\Helpers\CalHelper;

class ConsultationController extends Controller
{
    protected $repo;

    /**
     * Instantiate a new controller instance
     * @return void
     */
    public function __construct(
        MeetingRepository $repo
    ) {
        $this->repo = $repo;
    }

    /**
     * Get consultation pre requisite
     * @get ("/api/consultations/prerequisite")
     * @return array
     */
    public function preRequisite()
    {
        // Reuse the meeting pre-requisite functionality but filter for consultation-specific data
        $preRequisiteData = $this->repo->getPreRequisite();
        // Filter categories to only include consultation-related ones if they exist
        // For now, we'll return all meeting categories since consultations are a type of meeting
        return $this->ok($preRequisiteData);
    }

    /**
     * Get available hosts for consultations
     * @get ("/api/consultations/available-hosts")
     * @return array
     */
    public function getAvailableHosts(Request $request)
    {
        $duration = $request->query('duration', 30); // Default to 30 minutes
        
        // Get users who can host meetings (have appropriate permissions/roles)
        // For now, we'll return all users with active memberships as potential hosts
        $hosts = \App\Models\User::whereHas('roles', function($query) {
            $query->where('name', '!=', 'user');
        })->orWhere('meta->is_premium', true)
        ->select('id', 'name', 'email')
        ->get();
        
        // In a real implementation, you would check host availability based on their schedule
        // For now, we'll just return all potential hosts
        $availableHosts = $hosts->map(function($host) {
            return [
                'id' => $host->id,
                'name' => $host->name,
                'email' => $host->email,
                // In a real implementation, you would include availability information here
                'is_available' => true, // Placeholder
                'next_available' => null // Placeholder
            ];
        });
        
        return $this->ok([
            'hosts' => $availableHosts,
            'duration' => $duration
        ]);
    }

    /**
     * Create a consultation (meeting) from booking data
     * @post ("/api/consultations")
     * @return array
     */
    public function store(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('Creating consultation from booking', [
            'request_data' => $request->all(),
            'user_id' => auth()->id()
        ]);

        $validator = \Validator::make($request->all(), [
            'booking_id' => 'nullable|exists:consultation_bookings,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date|after:now',
            'duration' => 'required|integer|min:15|max:480',
            'patient_id' => 'required|exists:users,id',
            'emotional_detection_host_only' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            \DB::beginTransaction();

            $hostId = auth()->id();
            $patientId = $request->patient_id;
            $startTime = $request->start_time;
            $duration = $request->duration;

            // If booking_id is provided, use the booking's preferred_datetime
            if ($request->booking_id) {
                $booking = \App\Models\ConsultationBooking::findOrFail($request->booking_id);
                
                // Use the booking's preferred_datetime instead of the request's start_time
                $startTime = $booking->preferred_datetime->format('Y-m-d H:i:s');
                $duration = $booking->duration_minutes;
                
                // Update booking status
                $booking->status = 'scheduled';
                $booking->assigned_host_id = $hostId;
                $booking->save();
            }

            // Create the meeting
            $meeting = \App\Models\Meeting::create([
                'title' => $request->title,
                'agenda' => $request->description,
                'start_date_time' => $startTime,
                'period' => $duration,
                'user_id' => $hostId,
                'type' => 'consultation',
            ]);

            // Link booking to meeting if booking_id was provided
            if ($request->booking_id) {
                $booking->meeting_id = $meeting->id;
                $booking->save();
            }

            \DB::commit();

            return $this->success([
                'message' => 'Consultation created successfully',
                'meeting' => [
                    'id' => $meeting->id,
                    'uuid' => $meeting->uuid,
                    'title' => $meeting->title,
                    'start_date_time' => $meeting->start_date_time,
                    'duration' => $meeting->period,
                    'host_id' => $meeting->user_id,
                    'patient_id' => $patientId,
                ]
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Failed to create consultation', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return $this->error('Failed to create consultation: ' . $e->getMessage());
        }
    }
}