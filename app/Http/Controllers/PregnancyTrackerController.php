<?php

namespace App\Http\Controllers;

use App\Models\PregnancyRecord;
use App\Models\PregnancySymptom;
use App\Models\PregnancyAppointment;
use App\Models\BabyDevelopmentMilestone;
use App\Models\PregnancyHealthMetric;
use App\Models\PregnancyRiskFactor;
use App\Http\Requests\Pregnancy\StartPregnancyRequest;
use App\Http\Requests\Pregnancy\LogSymptomRequest;
use App\Http\Requests\Pregnancy\CreateAppointmentRequest;
use App\Http\Requests\Pregnancy\LogHealthMetricRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PregnancyTrackerController extends Controller
{
    /**
     * Test endpoint to verify controller is working
     * GET /api/pregnancy/test
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Pregnancy tracker controller is working',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get pregnancy week image placeholder
     * GET /api/pregnancy/week-image/{week}
     */
    public function getWeekImage($week): \Illuminate\Http\Response
    {
        $week = (int) $week;
        if ($week < 1 || $week > 40) {
            $week = 15; // Default to week 15
        }

        $html = view('pregnancy.week-image', ['week' => $week])->render();
        
        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Cache-Control', 'public, max-age=3600');
    }
    /**
     * Get pregnancy overview
     * GET /api/pregnancy/overview
     */
    public function getOverview(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active pregnancy found',
                    'data' => null,
                    'has_pregnancy' => false
                ], 200);
            }

            $currentWeek = $pregnancy->gestational_age_weeks;
            $dueDate = $pregnancy->due_date;
            $lmp = $pregnancy->lmp_date;
            $daysUntilDue = $pregnancy->getDaysUntilDue();
            $trimester = $pregnancy->trimester;
            $progressPercentage = $pregnancy->getPregnancyProgress();
            $babySize = $pregnancy->getBabySizeInfo();
            
            // Calculate next milestone based on current week
            $nextMilestone = $this->getNextMilestone($currentWeek);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_week' => $currentWeek,
                    'trimester' => $trimester,
                    'days_until_due' => $daysUntilDue,
                    'lmp' => $lmp ? $lmp->format('Y-m-d') : null,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'progress_percentage' => $progressPercentage,
                    'baby_size' => $babySize['size'] ?? 'Unknown',
                    'next_milestone' => $nextMilestone
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get pregnancy overview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get baby development
     * GET /api/pregnancy/development
     */
    public function getDevelopment(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active pregnancy found'
                ], 200);
            }

            $currentWeek = $pregnancy->gestational_age_weeks;
            $babySize = $this->getBabySizeForWeek($currentWeek);
            $keyDevelopments = $this->getKeyDevelopmentsForWeek($currentWeek);
            
            // Get development data for the current week
            $developmentData = $this->getDevelopmentDataForWeek($currentWeek);

            return response()->json([
                'success' => true,
                'data' => [
                    'week' => $currentWeek,
                    'summary' => $developmentData['summary'],
                    'tip' => $developmentData['tip'],
                    'milestones' => $developmentData['milestones'],
                    'baby_size' => $babySize['size'],
                    'development_image' => "http://127.0.0.1:8000/api/pregnancy/week-image/{$currentWeek}",
                    'key_developments' => $keyDevelopments
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get development data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pregnancy predictions (overview fields with computed values)
     * GET /api/pregnancy/predictions
     */
    public function getPredictions(): JsonResponse
    {
        try {
            $user = auth()->user();

            $cacheKey = "pregnancy_predictions_{$user->id}";
            $data = Cache::remember($cacheKey, 600, function () use ($user) {
                $pregnancy = PregnancyRecord::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

                if (!$pregnancy) {
                    return null;
                }

                // Assume model has helper methods; otherwise compute from lmp/due_date
                $due = $pregnancy->due_date;
                $lmp = $pregnancy->lmp_date;
                $today = Carbon::today();
                $gestWeeks = $pregnancy->gestational_age_weeks ?? ($lmp ? $today->diffInWeeks($lmp) : null);
                $trimester = $pregnancy->trimester ?? ($gestWeeks ? ($gestWeeks < 13 ? '1st' : ($gestWeeks < 27 ? '2nd' : '3rd')) : null);
                $daysUntilDue = $due ? $today->diffInDays($due, false) : null;

                return [
                    'lmp' => $lmp ? $lmp->format('Y-m-d') : null,
                    'due_date' => $due ? $due->format('Y-m-d') : null,
                    'current_week' => $gestWeeks,
                    'trimester' => $trimester,
                    'days_until_due' => $daysUntilDue,
                ];
            });

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active pregnancy found',
                    'data' => null
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get predictions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pregnancy appointments
     * GET /api/pregnancy/appointments
     */
    public function getAppointments(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $appointments = PregnancyAppointment::where('pregnancy_record_id', $pregnancy->id)
                ->orderBy('appointment_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $appointments->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                        'appointment_type' => $appointment->appointment_type,
                        'note' => $appointment->notes,
                        'status' => $appointment->status,
                        'doctor' => $appointment->doctor_name,
                        'location' => $appointment->clinic_name
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new pregnancy appointment
     * POST /api/pregnancy/appointments
     */
    public function createAppointment(CreateAppointmentRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active pregnancy found. Please start a pregnancy first.',
                    'error_code' => 'NO_PREGNANCY_RECORD'
                ], 200);
            }

            $appointment = PregnancyAppointment::create([
                'pregnancy_record_id' => $pregnancy->id,
                'user_id' => $user->id,
                'appointment_type' => $request->appointment_type,
                'doctor_name' => $request->doctor_name ?? null,
                'clinic_name' => $request->clinic_name ?? null,
                'clinic_address' => $request->clinic_address ?? null,
                'clinic_phone' => $request->clinic_phone ?? null,
                'appointment_date' => $request->appointment_date,
                'status' => 'scheduled',
                'notes' => $request->notes,
                'appointment_data' => $request->appointment_data ?? null,
                'reminder_sent' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => [
                    'id' => $appointment->id,
                    'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                    'appointment_type' => $appointment->appointment_type,
                    'note' => $appointment->notes
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an appointment
     * DELETE /api/pregnancy/appointments/{id}
     */
    public function deleteAppointment($id): JsonResponse
    {
        try {
            $user = auth()->user();
            $appointment = PregnancyAppointment::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found',
                ], 404);
            }

            $appointment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Appointment deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pregnancy symptoms
     * GET /api/pregnancy/symptoms
     */
    public function getSymptoms(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $symptoms = PregnancySymptom::where('pregnancy_record_id', $pregnancy->id)
                ->orderBy('symptom_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $symptoms->map(function ($symptom) {
                    return [
                        'id' => $symptom->id,
                        'symptom' => $symptom->symptom_name,
                        'date' => $symptom->symptom_date->format('Y-m-d'),
                        'severity' => $symptom->severity,
                        'notes' => $symptom->notes
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get symptoms: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log pregnancy symptoms
     * POST /api/pregnancy/symptoms
     */
    public function logSymptom(LogSymptomRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active pregnancy found. Please start a pregnancy first.',
                    'error_code' => 'NO_PREGNANCY_RECORD'
                ], 200);
            }

            $symptom = PregnancySymptom::create([
                'pregnancy_record_id' => $pregnancy->id,
                'user_id' => $user->id,
                'symptom_name' => $request->symptom,
                'symptom_date' => now()->toDateString(),
                'severity' => $request->severity,
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Symptom logged successfully',
                'data' => $symptom
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log symptom: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get health metrics
     * GET /api/pregnancy/health-metrics
     */
    public function getHealthMetrics(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $metrics = PregnancyHealthMetric::where('pregnancy_record_id', $pregnancy->id)
                ->orderBy('measurement_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $metrics->map(function ($metric) {
                    return [
                        'id' => $metric->id,
                        'date' => $metric->metric_date->format('Y-m-d'),
                        'weight' => $metric->weight,
                        'blood_pressure' => $metric->blood_pressure,
                        'water_intake' => $metric->water_intake,
                        'sleep' => $metric->sleep_hours,
                        'nutrition' => $metric->nutrition_quality,
                        'exercise' => $metric->exercise_level,
                        'mood' => $metric->mood,
                        'notes' => $metric->notes
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get health metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log health metrics
     * POST /api/pregnancy/health-metrics
     */
    public function logHealthMetric(LogHealthMetricRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active pregnancy found. Please start a pregnancy first.',
                    'error_code' => 'NO_PREGNANCY_RECORD'
                ], 200);
            }

            $metric = PregnancyHealthMetric::create([
                'pregnancy_record_id' => $pregnancy->id,
                'user_id' => $user->id,
                'metric_date' => now()->toDateString(),
                'weight' => $request->weight,
                'blood_pressure' => $request->blood_pressure,
                'water_intake' => $request->water_intake,
                'sleep_hours' => $request->sleep,
                'nutrition_quality' => $request->nutrition,
                'exercise_level' => $request->exercise,
                'mood' => $request->mood,
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Health metric logged successfully',
                'data' => $metric
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log health metric: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a new pregnancy
     * POST /api/pregnancy/start
     */
    public function startPregnancy(StartPregnancyRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check if user already has an active pregnancy
            $existingPregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($existingPregnancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active pregnancy record'
                ], 400);
            }

            // Create new pregnancy record
            $pregnancy = PregnancyRecord::create([
                'user_id' => $user->id,
                'lmp_date' => $request->lmp,
                'due_date' => $request->due_date ?? null,
                'status' => 'active'
            ]);

            // Calculate due date if not provided
            if (!$pregnancy->due_date) {
                $pregnancy->calculateDueDate();
            }

            // Calculate gestational age and trimester
            $pregnancy->calculateGestationalAge();
            $pregnancy->calculateTrimester();
            $pregnancy->save();

            return response()->json([
                'success' => true,
                'message' => 'Pregnancy tracking started successfully',
                'data' => [
                    'current_week' => $pregnancy->gestational_age_weeks,
                    'trimester' => $pregnancy->trimester,
                    'days_until_due' => $pregnancy->due_date ? $pregnancy->due_date->diffInDays(now()) : null
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start pregnancy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pregnancy timeline
     * GET /api/pregnancy/timeline
     */
    public function getTimeline(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $pregnancy = PregnancyRecord::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$pregnancy) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active pregnancy found'
                ], 200);
            }

            $currentWeek = $pregnancy->gestational_age_weeks;
            $timeline = [];

            // Generate timeline for current week and a few weeks ahead
            for ($week = max(1, $currentWeek - 2); $week <= min(40, $currentWeek + 4); $week++) {
                $timeline[] = [
                    'week' => $week,
                    'gestational_age' => $week . ' week' . ($week > 1 ? 's' : ''),
                    'baby_size' => $this->getBabySizeForWeek($week)['size'] ?? 'Unknown',
                    'baby_weight' => $this->getBabySizeForWeek($week)['weight'] ?? 'Unknown',
                    'key_developments' => $this->getKeyDevelopmentsForWeek($week),
                    'mother_changes' => $this->getMotherChangesForWeek($week),
                    'recommendations' => $this->getRecommendationsForWeek($week)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'current_week' => $currentWeek,
                    'timeline' => $timeline
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get timeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to get growth chart
     */
    private function getGrowthChart(): array
    {
        return [
            'week_1' => ['length' => '0.1 cm', 'weight' => '0.1 grams'],
            'week_2' => ['length' => '0.2 cm', 'weight' => '0.2 grams'],
            'week_3' => ['length' => '0.3 cm', 'weight' => '0.3 grams'],
            'week_4' => ['length' => '0.4 cm', 'weight' => '0.4 grams'],
            'week_5' => ['length' => '0.5 cm', 'weight' => '0.5 grams'],
            'week_6' => ['length' => '0.6 cm', 'weight' => '0.6 grams'],
            'week_7' => ['length' => '0.7 cm', 'weight' => '0.7 grams'],
            'week_8' => ['length' => '0.8 cm', 'weight' => '0.8 grams'],
            'week_9' => ['length' => '0.9 cm', 'weight' => '0.9 grams'],
            'week_10' => ['length' => '1.0 cm', 'weight' => '1.0 grams'],
            'week_11' => ['length' => '1.1 cm', 'weight' => '1.1 grams'],
            'week_12' => ['length' => '1.2 cm', 'weight' => '1.2 grams']
        ];
    }

    /**
     * Helper method to get baby size for a specific week
     */
    private function getBabySizeForWeek(int $week): array
    {
        $sizeChart = [
            4 => ['size' => 'Poppy seed', 'length' => 0.1, 'weight' => 0.1],
            5 => ['size' => 'Sesame seed', 'length' => 0.2, 'weight' => 0.2],
            6 => ['size' => 'Lentil', 'length' => 0.4, 'weight' => 0.4],
            7 => ['size' => 'Blueberry', 'length' => 0.7, 'weight' => 0.7],
            8 => ['size' => 'Kidney bean', 'length' => 1.2, 'weight' => 1.2],
            9 => ['size' => 'Grape', 'length' => 1.8, 'weight' => 1.8],
            10 => ['size' => 'Kumquat', 'length' => 2.5, 'weight' => 2.5],
            11 => ['size' => 'Fig', 'length' => 3.5, 'weight' => 3.5],
            12 => ['size' => 'Lime', 'length' => 4.5, 'weight' => 4.5],
            13 => ['size' => 'Lemon', 'length' => 5.5, 'weight' => 5.5],
            14 => ['size' => 'Peach', 'length' => 6.5, 'weight' => 6.5],
            15 => ['size' => 'Apple', 'length' => 7.5, 'weight' => 7.5],
            16 => ['size' => 'Avocado', 'length' => 8.5, 'weight' => 8.5],
            17 => ['size' => 'Pear', 'length' => 9.5, 'weight' => 9.5],
            18 => ['size' => 'Bell pepper', 'length' => 10.5, 'weight' => 10.5],
            19 => ['size' => 'Mango', 'length' => 11.5, 'weight' => 11.5],
            20 => ['size' => 'Banana', 'length' => 12.5, 'weight' => 12.5],
            21 => ['size' => 'Carrot', 'length' => 13.5, 'weight' => 13.5],
            22 => ['size' => 'Coconut', 'length' => 14.5, 'weight' => 14.5],
            23 => ['size' => 'Grapefruit', 'length' => 15.5, 'weight' => 15.5],
            24 => ['size' => 'Corn', 'length' => 16.5, 'weight' => 16.5],
            25 => ['size' => 'Cauliflower', 'length' => 17.5, 'weight' => 17.5],
            26 => ['size' => 'Lettuce', 'length' => 18.5, 'weight' => 18.5],
            27 => ['size' => 'Broccoli', 'length' => 19.5, 'weight' => 19.5],
            28 => ['size' => 'Eggplant', 'length' => 20.5, 'weight' => 20.5],
            29 => ['size' => 'Butternut squash', 'length' => 21.5, 'weight' => 21.5],
            30 => ['size' => 'Cabbage', 'length' => 22.5, 'weight' => 22.5],
            31 => ['size' => 'Pineapple', 'length' => 23.5, 'weight' => 23.5],
            32 => ['size' => 'Squash', 'length' => 24.5, 'weight' => 24.5],
            33 => ['size' => 'Pineapple', 'length' => 25.5, 'weight' => 25.5],
            34 => ['size' => 'Cantaloupe', 'length' => 26.5, 'weight' => 26.5],
            35 => ['size' => 'Honeydew melon', 'length' => 27.5, 'weight' => 27.5],
            36 => ['size' => 'Romaine lettuce', 'length' => 28.5, 'weight' => 28.5],
            37 => ['size' => 'Swiss chard', 'length' => 29.5, 'weight' => 29.5],
            38 => ['size' => 'Leek', 'length' => 30.5, 'weight' => 30.5],
            39 => ['size' => 'Mini watermelon', 'length' => 31.5, 'weight' => 31.5],
            40 => ['size' => 'Pumpkin', 'length' => 32.5, 'weight' => 32.5]
        ];

        return $sizeChart[$week] ?? $sizeChart[40];
    }

    /**
     * Helper method to get key developments for a specific week
     */
    private function getKeyDevelopmentsForWeek(int $week): array
    {
        $developments = [
            1 => ['Fertilization occurs'],
            2 => ['Implantation begins'],
            3 => ['Placenta starts forming'],
            4 => ['Neural tube begins to develop'],
            5 => ['Heart begins to beat'],
            6 => ['Basic facial features form'],
            7 => ['Arms and legs buds appear'],
            8 => ['Major organs begin developing'],
            9 => ['Baby starts moving'],
            10 => ['Fingernails and toenails form'],
            11 => ['Baby can make sucking motions'],
            12 => ['Sex can be determined'],
            13 => ['Baby can make facial expressions'],
            14 => ['Baby can hiccup'],
            15 => ['Baby can make sucking and swallowing motions'],
            16 => ['Baby can make sucking and swallowing motions'],
            17 => ['Baby can make sucking and swallowing motions'],
            18 => ['Baby can make sucking and swallowing motions'],
            19 => ['Baby can make sucking and swallowing motions'],
            20 => ['Baby can make sucking and swallowing motions'],
            21 => ['Baby can make sucking and swallowing motions'],
            22 => ['Baby can make sucking and swallowing motions'],
            23 => ['Baby can make sucking and swallowing motions'],
            24 => ['Baby can make sucking and swallowing motions'],
            25 => ['Baby can make sucking and swallowing motions'],
            26 => ['Baby can make sucking and swallowing motions'],
            27 => ['Baby can make sucking and swallowing motions'],
            28 => ['Baby can make sucking and swallowing motions'],
            29 => ['Baby can make sucking and swallowing motions'],
            30 => ['Baby can make sucking and swallowing motions'],
            31 => ['Baby can make sucking and swallowing motions'],
            32 => ['Baby can make sucking and swallowing motions'],
            33 => ['Baby can make sucking and swallowing motions'],
            34 => ['Baby can make sucking and swallowing motions'],
            35 => ['Baby can make sucking and swallowing motions'],
            36 => ['Baby can make sucking and swallowing motions'],
            37 => ['Baby can make sucking and swallowing motions'],
            38 => ['Baby can make sucking and swallowing motions'],
            39 => ['Baby can make sucking and swallowing motions'],
            40 => ['Baby is ready for birth']
        ];

        return $developments[$week] ?? ['Development continues'];
    }

    /**
     * Helper method to get mother changes for a specific week
     */
    private function getMotherChangesForWeek(int $week): array
    {
        $changes = [
            1 => ['No visible changes yet'],
            2 => ['Possible implantation bleeding'],
            3 => ['No visible changes yet'],
            4 => ['No visible changes yet'],
            5 => ['No visible changes yet'],
            6 => ['No visible changes yet'],
            7 => ['No visible changes yet'],
            8 => ['No visible changes yet'],
            9 => ['No visible changes yet'],
            10 => ['No visible changes yet'],
            11 => ['No visible changes yet'],
            12 => ['No visible changes yet'],
            13 => ['No visible changes yet'],
            14 => ['No visible changes yet'],
            15 => ['No visible changes yet'],
            16 => ['No visible changes yet'],
            17 => ['No visible changes yet'],
            18 => ['No visible changes yet'],
            19 => ['No visible changes yet'],
            20 => ['No visible changes yet'],
            21 => ['No visible changes yet'],
            22 => ['No visible changes yet'],
            23 => ['No visible changes yet'],
            24 => ['No visible changes yet'],
            25 => ['No visible changes yet'],
            26 => ['No visible changes yet'],
            27 => ['No visible changes yet'],
            28 => ['No visible changes yet'],
            29 => ['No visible changes yet'],
            30 => ['No visible changes yet'],
            31 => ['No visible changes yet'],
            32 => ['No visible changes yet'],
            33 => ['No visible changes yet'],
            34 => ['No visible changes yet'],
            35 => ['No visible changes yet'],
            36 => ['No visible changes yet'],
            37 => ['No visible changes yet'],
            38 => ['No visible changes yet'],
            39 => ['No visible changes yet'],
            40 => ['Ready for labor']
        ];

        return $changes[$week] ?? ['Body continues to adapt'];
    }

    /**
     * Helper method to get recommendations for a specific week
     */
    private function getRecommendationsForWeek(int $week): array
    {
        $recommendations = [
            1 => ['Start taking folic acid'],
            2 => ['Continue prenatal vitamins'],
            3 => ['Continue prenatal vitamins'],
            4 => ['Continue prenatal vitamins'],
            5 => ['Continue prenatal vitamins'],
            6 => ['Continue prenatal vitamins'],
            7 => ['Continue prenatal vitamins'],
            8 => ['Continue prenatal vitamins'],
            9 => ['Continue prenatal vitamins'],
            10 => ['Continue prenatal vitamins'],
            11 => ['Continue prenatal vitamins'],
            12 => ['Continue prenatal vitamins'],
            13 => ['Continue prenatal vitamins'],
            14 => ['Continue prenatal vitamins'],
            15 => ['Continue prenatal vitamins'],
            16 => ['Continue prenatal vitamins'],
            17 => ['Continue prenatal vitamins'],
            18 => ['Continue prenatal vitamins'],
            19 => ['Continue prenatal vitamins'],
            20 => ['Continue prenatal vitamins'],
            21 => ['Continue prenatal vitamins'],
            22 => ['Continue prenatal vitamins'],
            23 => ['Continue prenatal vitamins'],
            24 => ['Continue prenatal vitamins'],
            25 => ['Continue prenatal vitamins'],
            26 => ['Continue prenatal vitamins'],
            27 => ['Continue prenatal vitamins'],
            28 => ['Continue prenatal vitamins'],
            29 => ['Continue prenatal vitamins'],
            30 => ['Continue prenatal vitamins'],
            31 => ['Continue prenatal vitamins'],
            32 => ['Continue prenatal vitamins'],
            33 => ['Continue prenatal vitamins'],
            34 => ['Continue prenatal vitamins'],
            35 => ['Continue prenatal vitamins'],
            36 => ['Continue prenatal vitamins'],
            37 => ['Continue prenatal vitamins'],
            38 => ['Continue prenatal vitamins'],
            39 => ['Continue prenatal vitamins'],
            40 => ['Prepare for labor']
        ];

        return $recommendations[$week] ?? ['Continue with prenatal care'];
    }

    /**
     * Helper method to get the next milestone for a specific week
     */
    private function getNextMilestone(int $currentWeek): string
    {
        $milestones = [
            1 => 'Fertilization occurs',
            2 => 'Implantation begins',
            3 => 'Placenta starts forming',
            4 => 'Neural tube begins to develop',
            5 => 'Heart begins to beat',
            6 => 'Basic facial features form',
            7 => 'Arms and legs buds appear',
            8 => 'Major organs begin developing',
            9 => 'Baby starts moving',
            10 => 'Fingernails and toenails form',
            11 => 'Baby can make sucking motions',
            12 => 'Sex can be determined',
            13 => 'Baby can make facial expressions',
            14 => 'Baby can hiccup',
            15 => 'Baby can hear sounds',
            16 => 'Baby can make sucking and swallowing motions',
            17 => 'Baby can make sucking and swallowing motions',
            18 => 'Baby can make sucking and swallowing motions',
            19 => 'Baby can make sucking and swallowing motions',
            20 => 'Anatomy scan at 20 weeks',
            21 => 'Baby can make sucking and swallowing motions',
            22 => 'Baby can make sucking and swallowing motions',
            23 => 'Baby can make sucking and swallowing motions',
            24 => 'Baby can make sucking and swallowing motions',
            25 => 'Baby can make sucking and swallowing motions',
            26 => 'Baby can make sucking and swallowing motions',
            27 => 'Baby can make sucking and swallowing motions',
            28 => 'Baby can make sucking and swallowing motions',
            29 => 'Baby can make sucking and swallowing motions',
            30 => 'Baby can make sucking and swallowing motions',
            31 => 'Baby can make sucking and swallowing motions',
            32 => 'Baby can make sucking and swallowing motions',
            33 => 'Baby can make sucking and swallowing motions',
            34 => 'Baby can make sucking and swallowing motions',
            35 => 'Baby can make sucking and swallowing motions',
            36 => 'Baby can make sucking and swallowing motions',
            37 => 'Baby can make sucking and swallowing motions',
            38 => 'Baby can make sucking and swallowing motions',
            39 => 'Baby can make sucking and swallowing motions',
            40 => 'Baby is ready for birth'
        ];

        foreach ($milestones as $week => $milestone) {
            if ($week > $currentWeek) {
                return $milestone;
            }
        }

        return 'No upcoming milestones';
    }

    /**
     * Get development data for a specific week
     */
    private function getDevelopmentDataForWeek(int $week): array
    {
        $developmentData = [
            15 => [
                'summary' => 'Baby can hear sounds',
                'tip' => 'Talk and sing to your baby',
                'milestones' => 'Baby\'s hearing is developing rapidly'
            ],
            16 => [
                'summary' => 'Baby is growing rapidly',
                'tip' => 'Stay hydrated and eat well',
                'milestones' => 'Baby\'s bones are getting stronger'
            ],
            17 => [
                'summary' => 'Baby can make sucking motions',
                'tip' => 'Practice relaxation techniques',
                'milestones' => 'Baby is practicing breathing movements'
            ],
            18 => [
                'summary' => 'Baby can hiccup',
                'tip' => 'You might feel baby\'s movements',
                'milestones' => 'Baby\'s nervous system is developing'
            ],
            19 => [
                'summary' => 'Baby can make facial expressions',
                'tip' => 'Rest when you need to',
                'milestones' => 'Baby\'s facial muscles are developing'
            ],
            20 => [
                'summary' => 'Halfway through pregnancy!',
                'tip' => 'Time for the anatomy scan',
                'milestones' => 'All major organs are formed'
            ]
        ];

        return $developmentData[$week] ?? [
            'summary' => 'Baby is developing well',
            'tip' => 'Continue with prenatal care',
            'milestones' => 'Baby is growing and developing normally'
        ];
    }
} 