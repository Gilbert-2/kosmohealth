<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingEmotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeetingEmotionController extends Controller
{
    // POST /api/meetings/{meeting}/emotion/start
    public function start($meetingId, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Find meeting by ID or UUID
            $meeting = Meeting::where('id', $meetingId)
                ->orWhere('uuid', $meetingId)
                ->first();
            
            if (!$meeting) {
                return $this->error(['message' => 'Meeting not found'], 404);
            }
            
            // Check if user is the host or a participant of the meeting
            $isHost = $meeting->user_id === $user->id;
            $isParticipant = $meeting->invitees()
                ->whereHas('contact', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();
            
            if (!$isHost && !$isParticipant) {
                return $this->error(['message' => 'You do not have permission to access this meeting'], 403);
            }
            
            // Check if meeting is accessible
            try {
                $meeting->isAccessible();
            } catch (\Exception $e) {
                return $this->error(['message' => 'Meeting is not accessible: ' . $e->getMessage()], 403);
            }

            // Create or find existing emotion session
            $emotion = MeetingEmotion::firstOrCreate(
                ['meeting_id' => $meeting->id],
                [
                    'host_id' => $meeting->user_id,
                    'patient_id' => $isHost ? 
                        ($meeting->invitees()->first()?->user_id ?? $user->id) : 
                        $user->id,
                    'started_at' => now(),
                    'timeline' => [],
                    'summary' => [],
                ]
            );

            Log::info('Emotion tracking started', [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'is_host' => $isHost
            ]);

            return $this->success([
                'message' => 'Emotion session started', 
                'data' => $emotion,
                'meeting_info' => [
                    'id' => $meeting->id,
                    'uuid' => $meeting->uuid,
                    'title' => $meeting->title,
                    'is_host' => $isHost
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error starting emotion tracking', [
                'meeting_id' => $meetingId,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->error(['message' => 'Failed to start emotion tracking: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/meetings/{meeting}/emotion/events
    public function events($meetingId, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Find meeting by ID or UUID
            $meeting = Meeting::where('id', $meetingId)
                ->orWhere('uuid', $meetingId)
                ->first();
            
            if (!$meeting) {
                return $this->error(['message' => 'Meeting not found'], 404);
            }
            
            // Check if user is the host or a participant of the meeting
            $isHost = $meeting->user_id === $user->id;
            $isParticipant = $meeting->invitees()
                ->whereHas('contact', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();
            
            if (!$isHost && !$isParticipant) {
                return $this->error(['message' => 'You do not have permission to access this meeting'], 403);
            }
            
            // Check if meeting is accessible
            try {
                $meeting->isAccessible();
            } catch (\Exception $e) {
                return $this->error(['message' => 'Meeting is not accessible: ' . $e->getMessage()], 403);
            }

            // More flexible validation for emotion events
            $data = $request->validate([
                'events' => 'nullable|array',
                'emotions' => 'nullable|array', // Allow direct emotions array
                'timestamp' => 'nullable|date', // Allow single timestamp
                'emotion_data' => 'nullable|array', // Allow alternative format
            ]);

            $emotion = MeetingEmotion::where('meeting_id', $meeting->id)->first();
            if (!$emotion) {
                return $this->error(['message' => 'Emotion session not started. Please start emotion tracking first.'], 400);
            }

            $timeline = $emotion->timeline ?? [];
            
            // Handle different data formats from frontend
            if (!empty($data['events']) && is_array($data['events'])) {
                // Format: events array with t and emotions
                foreach ($data['events'] as $event) {
                    if (isset($event['t']) && isset($event['emotions'])) {
                        $timeline[] = [
                            't' => $event['t'],
                            'emotions' => $event['emotions']
                        ];
                    }
                }
            } elseif (!empty($data['emotions']) && is_array($data['emotions'])) {
                // Format: direct emotions with timestamp
                $timestamp = $data['timestamp'] ?? now()->toISOString();
                $timeline[] = [
                    't' => $timestamp,
                    'emotions' => $data['emotions']
                ];
            } elseif (!empty($data['emotion_data']) && is_array($data['emotion_data'])) {
                // Format: emotion_data object
                $timestamp = now()->toISOString();
                $timeline[] = [
                    't' => $timestamp,
                    'emotions' => $data['emotion_data']
                ];
            } else {
                // No valid data provided
                return $this->error(['message' => 'No valid emotion data provided. Expected format: events array or emotions object.'], 400);
            }

            $emotion->timeline = $timeline;
            $emotion->save();

            Log::info('Emotion events recorded', [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'events_count' => count($timeline),
                'data_format' => array_keys($data)
            ]);

            return $this->success([
                'message' => 'Events recorded successfully', 
                'events_count' => count($timeline),
                'total_events' => count($timeline),
                'last_event' => end($timeline)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error in emotion events', [
                'meeting_id' => $meetingId,
                'user_id' => $request->user()?->id,
                'validation_errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return $this->error(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error recording emotion events', [
                'meeting_id' => $meetingId,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            
            return $this->error(['message' => 'Failed to record events: ' . $e->getMessage()], 500);
        }
    }

    // POST /api/meetings/{meeting}/emotion/end
    public function end($meetingId, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Find meeting by ID or UUID
            $meeting = Meeting::where('id', $meetingId)
                ->orWhere('uuid', $meetingId)
                ->first();
            
            if (!$meeting) {
                return $this->error(['message' => 'Meeting not found'], 404);
            }
            
            // Check if user is the host or a participant of the meeting
            $isHost = $meeting->user_id === $user->id;
            $isParticipant = $meeting->invitees()
                ->whereHas('contact', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();
            
            if (!$isHost && !$isParticipant) {
                return $this->error(['message' => 'You do not have permission to access this meeting'], 403);
            }
            
            // Check if meeting is accessible
            try {
                $meeting->isAccessible();
            } catch (\Exception $e) {
                return $this->error(['message' => 'Meeting is not accessible: ' . $e->getMessage()], 403);
            }

            $emotion = MeetingEmotion::where('meeting_id', $meeting->id)->first();
            if (!$emotion) {
                return $this->error(['message' => 'Emotion session not found. Please start emotion tracking first.'], 400);
            }

            $emotion->ended_at = now();
            $emotion->summary = $this->computeSummary($emotion->timeline ?? []);
            $emotion->save();

            Log::info('Emotion tracking ended', [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'is_host' => $isHost,
                'events_count' => count($emotion->timeline ?? [])
            ]);

            return $this->success([
                'message' => 'Emotion session ended', 
                'data' => $emotion,
                'summary' => $emotion->summary
            ]);

        } catch (\Exception $e) {
            Log::error('Error ending emotion tracking', [
                'meeting_id' => $meetingId,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->error(['message' => 'Failed to end emotion tracking: ' . $e->getMessage()], 500);
        }
    }

    // GET /api/meetings/{meeting}/emotion/report
    public function report($meetingId, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Find meeting by ID or UUID
            $meeting = Meeting::where('id', $meetingId)
                ->orWhere('uuid', $meetingId)
                ->first();
            
            if (!$meeting) {
                return $this->error(['message' => 'Meeting not found'], 404);
            }
            
            // Check if user is the host or a participant of the meeting
            $isHost = $meeting->user_id === $user->id;
            $isParticipant = $meeting->invitees()
                ->whereHas('contact', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->exists();
            
            if (!$isHost && !$isParticipant) {
                return $this->error(['message' => 'You do not have permission to access this meeting'], 403);
            }
            
            // Check if meeting is accessible
            try {
                $meeting->isAccessible();
            } catch (\Exception $e) {
                return $this->error(['message' => 'Meeting is not accessible: ' . $e->getMessage()], 403);
            }

            $emotion = MeetingEmotion::where('meeting_id', $meeting->id)->first();
            if (!$emotion) {
                return $this->ok(['data' => null, 'message' => 'No emotion data available for this meeting']);
            }
            
            if (!$emotion->summary) {
                $emotion->summary = $this->computeSummary($emotion->timeline ?? []);
                $emotion->save();
            }
            
            // Create a frontend-compatible response structure
            $summary = $emotion->summary ?? [];
            $response = [
                'data' => [
                    'id' => $emotion->id,
                    'meeting_id' => $emotion->meeting_id,
                    'host_id' => $emotion->host_id,
                    'patient_id' => $emotion->patient_id,
                    'started_at' => $emotion->started_at,
                    'ended_at' => $emotion->ended_at,
                    'timeline' => $emotion->timeline ?? [],
                    'summary' => [
                        'dominant_emotion' => $summary['dominant'] ?? null,
                        'averages' => $summary['averages'] ?? [],
                        'points' => $summary['points'] ?? 0,
                        'dominant' => $summary['dominant'] ?? null, // Keep both for compatibility
                    ]
                ],
                'meeting_info' => [
                    'id' => $meeting->id,
                    'uuid' => $meeting->uuid,
                    'title' => $meeting->title,
                    'is_host' => $isHost
                ]
            ];
            
            return $this->ok($response);

        } catch (\Exception $e) {
            Log::error('Error getting emotion report', [
                'meeting_id' => $meetingId,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->error(['message' => 'Failed to get emotion report: ' . $e->getMessage()], 500);
        }
    }

    private function computeSummary(array $timeline): array
    {
        $totals = [];
        $counts = 0;
        foreach ($timeline as $point) {
            $emotions = $point['emotions'] ?? [];
            foreach ($emotions as $name => $score) {
                $totals[$name] = ($totals[$name] ?? 0) + (float)$score;
            }
            $counts++;
        }
        $averages = [];
        foreach ($totals as $name => $sum) {
            $averages[$name] = $counts ? $sum / $counts : 0;
        }
        arsort($averages);
        $dominant = count($averages) ? array_key_first($averages) : null;
        return [
            'dominant' => $dominant,
            'averages' => $averages,
            'points' => $counts,
        ];
    }
}
