<?php

namespace App\Http\Controllers;

use App\Models\PeriodCycle;
use App\Models\PeriodSymptom;
use App\Events\PeriodDataUpdated;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PeriodTrackerController extends Controller
{
    /**
     * Get all period cycles
     */
    public function getCycles()
    {
        try {
            $cycles = PeriodCycle::where('user_id', auth()->id())
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(function($cycle) {
                    return [
                        'id' => $cycle->id,
                        'start_date' => $cycle->start_date,
                        'end_date' => $cycle->end_date,
                        'length' => $cycle->cycle_length ?? $this->calculateCycleLength($cycle),
                        'flow_intensity' => $cycle->flow_intensity ?? 'Medium',
                        'symptoms' => $this->getCycleSymptoms($cycle->id),
                        'mood' => $cycle->mood ?? 'Normal',
                        'notes' => $cycle->notes,
                        'created_at' => $cycle->created_at->toISOString(),
                        'updated_at' => $cycle->updated_at->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $cycles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cycles'
            ], 500);
        }
    }

    /**
     * Calculate cycle length
     */
    private function calculateCycleLength($cycle)
    {
        if (!$cycle->end_date) {
            return null;
        }
        
        return Carbon::parse($cycle->start_date)->diffInDays(Carbon::parse($cycle->end_date)) + 1;
    }

    /**
     * Get symptoms for a cycle
     */
    private function getCycleSymptoms($cycleId)
    {
        return PeriodSymptom::where('cycle_id', $cycleId)
            ->pluck('symptom')
            ->toArray();
    }

    /**
     * Save a new period cycle
     */
    public function saveCycle(Request $request)
    {
        try {
            // Log the incoming request data
            \Log::info('Period cycle save request', [
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            // Handle both 'date' and 'start_date' fields
            $startDate = $request->input('start_date') ?? $request->input('date');
            $endDate = $request->input('end_date') ?? $request->input('date'); // Use same date if no end_date

            $request->validate([
                'start_date' => 'nullable|date',
                'date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'flow_intensity' => 'nullable|string',
                'symptoms' => 'nullable|array',
                'mood' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            // Ensure we have a start date
            if (!$startDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Start date is required',
                    'errors' => ['start_date' => ['Start date is required']]
                ], 422);
            }

            $user = auth()->user();
            
            // Calculate cycle length
            $cycleLength = null;
            if ($endDate && $endDate !== $startDate) {
                $cycleLength = round(Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1, 2);
            }

            $cycle = PeriodCycle::create([
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate ?? $startDate, // Use start_date as fallback
                'cycle_length' => $cycleLength,
                'flow_intensity' => $request->flow_intensity ?? 'Medium',
                'mood' => $request->mood ?? 'Normal',
                'notes' => $request->notes,
            ]);

            // Save symptoms if provided
            if ($request->symptoms && is_array($request->symptoms)) {
                foreach ($request->symptoms as $symptom) {
                    PeriodSymptom::create([
                        'user_id' => $user->id,
                        'cycle_id' => $cycle->id,
                        'date' => $startDate,
                        'symptom' => $symptom,
                    ]);
                }
            }

            // Return the created cycle with the exact structure
            $responseData = [
                'id' => $cycle->id,
                'start_date' => $cycle->start_date,
                'end_date' => $cycle->end_date,
                'length' => $cycle->cycle_length ?? $this->calculateCycleLength($cycle),
                'flow_intensity' => $cycle->flow_intensity,
                'symptoms' => $request->symptoms ?? [],
                'mood' => $cycle->mood,
                'notes' => $cycle->notes,
                'created_at' => $cycle->created_at->toISOString(),
                'updated_at' => $cycle->updated_at->toISOString()
            ];

            // Broadcast real-time update
            event(new PeriodDataUpdated($user->id, $responseData, 'cycle'));

            // Invalidate prediction cache for this user
            Cache::forget("period_predictions_{$user->id}");

            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save cycle: ' . $e->getMessage()
            ], 500);
        }
    }





    /**
     * Log period symptoms
     */
    public function logSymptoms(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'flow_intensity' => 'nullable|string',
            'symptoms' => 'nullable|array',
            'mood' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            $user = auth()->user();
            
            // Handle both 'date' and 'start_date' fields
            $date = $request->input('date') ?? $request->input('start_date');
            
            if (!$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date is required',
                    'errors' => ['date' => ['Date is required']]
                ], 422);
            }

            // Create or update symptoms for the date
            $symptomIds = [];
            if ($request->symptoms && is_array($request->symptoms)) {
                foreach ($request->symptoms as $symptom) {
                    $symptomRecord = PeriodSymptom::create([
                        'user_id' => $user->id,
                        'date' => $date,
                        'symptom' => $symptom,
                        'flow_intensity' => $request->flow_intensity,
                        'mood' => $request->mood,
                        'notes' => $request->notes,
                    ]);
                    $symptomIds[] = $symptomRecord->id;
                }
            }

            // Return the created symptom data
            $responseData = [
                'id' => $symptomIds[0] ?? null,
                'date' => $date,
                'flow_intensity' => $request->flow_intensity ?? 'Medium',
                'symptoms' => $request->symptoms ?? [],
                'mood' => $request->mood ?? 'Normal',
                'notes' => $request->notes,
                'created_at' => now()->toISOString()
            ];

            // Broadcast real-time update
            event(new PeriodDataUpdated($user->id, $responseData, 'symptom'));

            // Invalidate prediction cache for this user
            Cache::forget("period_predictions_{$user->id}");

            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log symptoms: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhanced track symptoms for the modal system
     */
    public function trackSymptoms(Request $request)
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'symptoms' => 'required|array|min:1',
            'symptoms.*.type' => 'required|string|max:50',
            'symptoms.*.severity' => 'required|integer|min:1|max:5',
            'symptoms.*.notes' => 'nullable|string|max:500'
        ]);

        try {
            $user = auth()->user();
            $date = $request->date;
            $symptoms = $request->symptoms;

            // Security: Prevent spam by limiting entries per day
            $existingCount = PeriodSymptom::where('user_id', $user->id)
                ->where('date', $date)
                ->count();

            if ($existingCount > 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum symptoms per day exceeded'
                ], 429);
            }

            // Remove existing symptoms for the date (replace mode)
            PeriodSymptom::where('user_id', $user->id)
                ->where('date', $date)
                ->delete();

            // Add new symptoms
            $savedSymptoms = [];
            foreach ($symptoms as $symptom) {
                $savedSymptoms[] = PeriodSymptom::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'symptom' => $symptom['type'],
                    'severity' => $symptom['severity'] ?? 3,
                    'notes' => $symptom['notes'] ?? null
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'symptoms' => $savedSymptoms,
                    'message' => 'Symptoms tracked successfully'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error tracking symptoms', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track symptoms'
            ], 500);
        }
    }

    /**
     * Enhanced log period for the modal system
     */
    public function logPeriod(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today',
            'flow_intensity' => 'required|in:light,medium,heavy',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $user = auth()->user();

            // Check for overlapping cycles
            $overlapping = PeriodCycle::where('user_id', $user->id)
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date ?? $request->start_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date ?? $request->start_date]);
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Overlapping cycle dates detected'
                ], 422);
            }

            // Create period cycle
            $cycle = PeriodCycle::create([
                'user_id' => $user->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'flow_intensity' => $request->flow_intensity,
                'notes' => $request->notes,
                'is_predicted' => false
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cycle' => $cycle,
                    'message' => 'Period logged successfully'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error logging period', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to log period'
            ], 500);
        }
    }

    /**
     * Get dashboard data for modal system
     */
    public function getDashboardData(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Get cycles directly from database
            $cycles = PeriodCycle::where('user_id', $user->id)
                ->orderBy('start_date', 'desc')
                ->get();
            
            // Get current cycle phase
            $statistics = $this->calculateCycleStatistics($cycles);
            $currentPhase = $this->getCurrentCyclePhase($cycles->first(), $statistics);
            
            // Get recent cycles
            $recentCycles = $cycles->take(6)->map(function ($cycle) {
                return [
                    'id' => $cycle->id,
                    'start_date' => optional($cycle->start_date)->toDateString(),
                    'end_date' => optional($cycle->end_date)->toDateString(),
                    'length' => $cycle->cycle_length ?? ($cycle->end_date ? Carbon::parse($cycle->start_date)->diffInDays(Carbon::parse($cycle->end_date)) + 1 : null),
                    'flow_intensity' => $cycle->flow_intensity,
                    'mood' => $cycle->mood,
                ];
            });

            // Get recent symptoms (last 30 days)
            $recentSymptoms = PeriodSymptom::where('user_id', $user->id)
                ->where('date', '>=', Carbon::now()->subDays(30))
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy('date');

            // Calculate stats with meaningful defaults
            $statsFull = $this->calculateUserStats($user);
            
            // Provide meaningful defaults when has_minimum_data is false
            $hasMinimumData = $cycles->count() >= 2;
            
            if (!$hasMinimumData) {
                // Provide encouraging defaults for new users
                $statsFull = array_merge($statsFull, [
                    'average_cycle_length' => $stats['average_cycle_length'] ?? 28, // Default cycle length
                    'average_period_length' => $stats['average_period_length'] ?? 5, // Default period length
                    'next_predicted' => $stats['next_predicted'] ?? Carbon::now()->addDays(28)->toDateString(), // Estimate
                    'cycle_regularity' => 'tracking', // Encourage more tracking
                    'insights' => [
                        'Start tracking your cycles to get personalized insights!',
                        'Log at least 2-3 cycles for accurate predictions.',
                        'Track symptoms to understand your patterns.'
                    ]
                ]);
                
                $currentPhase = array_merge($currentPhase, [
                    'phase' => $currentPhase['phase'] ?? 'tracking',
                    'description' => $currentPhase['description'] ?? 'Keep tracking to discover your cycle phase!',
                    'day' => $currentPhase['day'] ?? 1,
                    'encouragement' => 'You\'re doing great! Keep tracking to unlock personalized insights.'
                ]);
            }

            // Augment current_phase with countdowns and fertile flag
            $pred = $this->getPredictions()->getData();
            $predData = isset($pred->data) ? (array) $pred->data : [];
            $currentPhaseOut = [
                'phase' => $currentPhase['phase'] ?? null,
                'days_until_period' => $predData['days_until_period'] ?? null,
                'days_until_ovulation' => $predData['days_until_ovulation'] ?? null,
                'is_fertile_window' => isset($predData['fertile_window_start'], $predData['fertile_window_end'])
                    ? Carbon::now()->between(
                        Carbon::parse($predData['fertile_window_start']),
                        Carbon::parse($predData['fertile_window_end'])
                    ) : false,
            ];

            $statsOut = [
                'average_cycle_length' => $statsFull['average_cycle_length'] ?? $statistics['average_cycle_length'],
                'last_period' => optional($cycles->first())->start_date ? Carbon::parse($cycles->first()->start_date)->toDateString() : null,
                'total_cycles' => PeriodCycle::where('user_id', $user->id)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'current_phase' => $currentPhaseOut,
                    'stats' => $statsOut,
                    'recent_cycles' => $recentCycles,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data'
            ], 500);
        }
    }

    /**
     * Get calendar data for modal system
     */
    public function getCalendarData(Request $request)
    {
        try {
            $user = auth()->user();
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            // Get cycles for the month
            $cycles = PeriodCycle::where('user_id', $user->id)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function($q) use ($startDate, $endDate) {
                              $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                          });
                })
                ->get();

            // Get symptoms for the month
            $symptoms = PeriodSymptom::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy('date');

            // Build calendar data
            $calendarData = [];

            // Add period data
            foreach ($cycles as $cycle) {
                $currentDate = Carbon::parse($cycle->start_date);
                $endDate = Carbon::parse($cycle->end_date ?? $cycle->start_date);
                
                while ($currentDate <= $endDate && $currentDate->month == $month) {
                    $dateKey = $currentDate->toDateString();
                    $calendarData[$dateKey] = [
                        'type' => 'period',
                        'data' => [
                            'flow_intensity' => $cycle->flow_intensity ?? 'Medium',
                            'symptoms' => $symptoms->get($dateKey, collect())->pluck('symptom')->toArray(),
                            'mood' => $cycle->mood ?? 'Normal'
                        ]
                    ];
                    $currentDate->addDay();
                }
            }

            // Add ovulation and fertile window predictions
            $predictions = $this->getPredictions();
            if ($predictions->getData()->success) {
                $predictionData = $predictions->getData()->data;
                
                // Add ovulation
                if ($predictionData->ovulation_date) {
                    $ovulationDate = Carbon::parse($predictionData->ovulation_date);
                    if ($ovulationDate->month == $month) {
                        $calendarData[$predictionData->ovulation_date] = [
                            'type' => 'ovulation',
                            'data' => [
                                'confidence' => $predictionData->confidence
                            ]
                        ];
                    }
                }

                // Add fertile window
                if ($predictionData->fertile_window_start && $predictionData->fertile_window_end) {
                    $fertileStart = Carbon::parse($predictionData->fertile_window_start);
                    $fertileEnd = Carbon::parse($predictionData->fertile_window_end);
                    
                    $currentDate = $fertileStart->copy();
                    while ($currentDate <= $fertileEnd && $currentDate->month == $month) {
                        $dateKey = $currentDate->toDateString();
                        if (!isset($calendarData[$dateKey])) {
                            $calendarData[$dateKey] = [
                                'type' => 'fertile',
                                'data' => [
                                    'window_start' => $currentDate->eq($fertileStart)
                                ]
                            ];
                        }
                        $currentDate->addDay();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $calendarData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load calendar data'
            ], 500);
        }
    }

    /**
     * Calculate user statistics
     */
    private function calculateUserStats($user)
    {
        $cycles = PeriodCycle::where('user_id', $user->id)
            ->where('is_predicted', false)
            ->orderBy('start_date', 'desc')
            ->limit(12)
            ->get();

        if ($cycles->count() < 2) {
            // Provide encouraging defaults for new users
            $lastPeriod = $cycles->first()?->start_date ?? Carbon::now()->subDays(7)->toDateString();
            $nextPredicted = Carbon::parse($lastPeriod)->addDays(28)->toDateString();
            
            return [
                'average_cycle_length' => 28, // Standard cycle length
                'average_period_length' => 5, // Standard period length
                'total_cycles' => $cycles->count(),
                'last_period' => $lastPeriod,
                'next_predicted' => $nextPredicted,
                'cycle_regularity' => 'tracking',
                'confidence_level' => 'low',
                'insights' => [
                    'Start tracking your cycles to get personalized insights!',
                    'Log at least 2-3 cycles for accurate predictions.',
                    'Track symptoms to understand your patterns.'
                ],
                'encouragement' => 'You\'re doing great! Keep tracking to unlock personalized insights.'
            ];
        }

        // Calculate average cycle length
        $cycleLengths = [];
        for ($i = 0; $i < $cycles->count() - 1; $i++) {
            $current = Carbon::parse($cycles[$i]->start_date);
            $next = Carbon::parse($cycles[$i + 1]->start_date);
            $cycleLengths[] = $next->diffInDays($current);
        }

        $avgCycleLength = count($cycleLengths) > 0 ? round(array_sum($cycleLengths) / count($cycleLengths)) : 28;

        // Calculate average period length
        $periodLengths = $cycles->filter(function($cycle) {
            return $cycle->end_date !== null;
        })->map(function($cycle) {
            return Carbon::parse($cycle->start_date)->diffInDays(Carbon::parse($cycle->end_date)) + 1;
        });

        $avgPeriodLength = $periodLengths->count() > 0 ? round($periodLengths->avg()) : 5;

        // Predict next period
        $nextPredicted = null;
        if ($avgCycleLength && $cycles->first()) {
            $nextPredicted = Carbon::parse($cycles->first()->start_date)->addDays($avgCycleLength);
        }

        // Calculate confidence level
        $confidenceLevel = 'high';
        if ($cycles->count() < 3) {
            $confidenceLevel = 'medium';
        } elseif ($cycles->count() < 6) {
            $confidenceLevel = 'low';
        }

        // Generate insights
        $insights = [];
        if ($avgCycleLength > 0) {
            $insights[] = "Your average cycle length is {$avgCycleLength} days";
        }
        if ($avgPeriodLength > 0) {
            $insights[] = "Your average period length is {$avgPeriodLength} days";
        }
        if ($cycles->count() >= 3) {
            $insights[] = "You have a good amount of data for accurate predictions";
        }

        return [
            'average_cycle_length' => $avgCycleLength,
            'average_period_length' => $avgPeriodLength,
            'total_cycles' => PeriodCycle::where('user_id', $user->id)->count(),
            'last_period' => $cycles->first()?->start_date,
            'next_predicted' => $nextPredicted,
            'cycle_regularity' => $this->calculateCycleRegularity($cycleLengths),
            'confidence_level' => $confidenceLevel,
            'insights' => $insights
        ];
    }

    /**
     * Calculate cycle regularity
     */
    private function calculateCycleRegularity($cycleLengths)
    {
        if (count($cycleLengths) < 2) {
            return 'tracking';
        }

        $avgLength = array_sum($cycleLengths) / count($cycleLengths);
        $variance = array_sum(array_map(function($length) use ($avgLength) {
            return pow($length - $avgLength, 2);
        }, $cycleLengths)) / count($cycleLengths);
        
        $stdDev = sqrt($variance);
        
        if ($stdDev <= 2) {
            return 'very_regular';
        } elseif ($stdDev <= 5) {
            return 'regular';
        } elseif ($stdDev <= 8) {
            return 'moderate';
        } else {
            return 'irregular';
        }
    }

    /**
     * Get logged symptoms for a date range
     */
    public function getSymptoms(Request $request)
    {
        $request->validate([
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'range' => 'integer|min:1|max:365'
        ]);

        try {
            $user = auth()->user();
            $query = PeriodSymptom::where('user_id', $user->id);

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('date', [$request->start_date, $request->end_date]);
            } elseif ($request->has('range')) {
                $query->where('date', '>=', Carbon::now()->subDays($request->range));
            } else {
                // Default to last 30 days
                $query->where('date', '>=', Carbon::now()->subDays(30));
            }

            $symptoms = $query->orderBy('date', 'desc')->get();
            $symptomsByDate = $symptoms->groupBy('date');
            $symptomsByType = $symptoms->groupBy('symptom');

            // Get available symptom types
            $availableSymptoms = $this->getAvailableSymptomTypes();

            return response()->json([
                'success' => true,
                'data' => [
                    'symptoms' => $symptoms,
                    'symptoms_by_date' => $symptomsByDate,
                    'symptoms_by_type' => $symptomsByType,
                    'available_symptoms' => $availableSymptoms
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting symptoms', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load symptoms'
            ], 500);
        }
    }

    /**
     * Get available symptom types
     */
    public function getAvailableSymptoms()
    {
        try {
            $symptoms = [
                [
                    'id' => 1,
                    'name' => 'Cramps',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 2,
                    'name' => 'Headache',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 3,
                    'name' => 'Bloating',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 4,
                    'name' => 'Fatigue',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 5,
                    'name' => 'Breast Tenderness',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 6,
                    'name' => 'Acne',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 7,
                    'name' => 'Food Cravings',
                    'category' => 'behavioral',
                    'icon' => 'activity'
                ],
                [
                    'id' => 8,
                    'name' => 'Back Pain',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 9,
                    'name' => 'Nausea',
                    'category' => 'physical',
                    'icon' => 'activity'
                ],
                [
                    'id' => 10,
                    'name' => 'Mood Swings',
                    'category' => 'emotional',
                    'icon' => 'activity'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $symptoms
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load symptoms'
            ], 500);
        }
    }
    
    /**
     * Get predictions and insights
     */
    public function getPredictions()
    {
        try {
            $user = auth()->user();
            
            $cacheKey = "period_predictions_{$user->id}";
            $payload = Cache::remember($cacheKey, 600, function () use ($user) {
                $cycles = PeriodCycle::where('user_id', $user->id)
                    ->where('is_predicted', false)
                    ->orderBy('start_date', 'desc')
                    ->limit(12)
                    ->get();

                $statistics = $this->calculateCycleStatistics($cycles);
                $predictions = $this->generatePredictions($cycles, $statistics);
                $confidence = $this->calculatePredictionConfidence($cycles);

                $avgPeriodLength = null;
                if ($cycles->count() > 0) {
                    $periodLengths = $cycles->filter(function ($cycle) {
                        return $cycle->end_date !== null;
                    })->map(function ($cycle) {
                        return Carbon::parse($cycle->start_date)->diffInDays(Carbon::parse($cycle->end_date)) + 1;
                    });
                    $avgPeriodLength = $periodLengths->count() > 0 ? round($periodLengths->avg()) : 5;
                }

                $nextPeriod = isset($predictions['next_period']) ? Carbon::parse($predictions['next_period']) : null;
                $ovulation = $nextPeriod ? $nextPeriod->copy()->subDays(14) : null;

                // Determine current phase
                $phase = 'tracking';
                if ($cycles->first() && $statistics['average_cycle_length']) {
                    $lastStart = Carbon::parse($cycles->first()->start_date);
                    $cycleDay = ($lastStart->diffInDays(Carbon::now()) % $statistics['average_cycle_length']) + 1;
                    if ($cycleDay <= ($avgPeriodLength ?? 5)) {
                        $phase = 'period';
                    } elseif ($ovulation) {
                        $daysToOvulation = Carbon::now()->diffInDays($ovulation, false);
                        if ($daysToOvulation <= 1 && $daysToOvulation >= -1) {
                            $phase = 'ovulation';
                        } elseif ($daysToOvulation <= 6 && $daysToOvulation >= -4) {
                            $phase = 'fertile';
                        } else {
                            $phase = $cycleDay < 14 ? 'follicular' : 'luteal';
                        }
                    }
                }

                return [
                    'next_period_date' => $predictions['next_period'] ?? null,
                    'fertile_window_start' => $predictions['fertile_window']['start'] ?? null,
                    'fertile_window_end' => $predictions['fertile_window']['end'] ?? null,
                    'ovulation_date' => $this->calculateOvulationDate($predictions['next_period'] ?? null),
                    'period_start_date' => $predictions['next_period'] ?? null,
                    'period_end_date' => isset($predictions['next_period']) && $avgPeriodLength
                        ? Carbon::parse($predictions['next_period'])->copy()->addDays(($avgPeriodLength ?? 5) - 1)->toDateString()
                        : null,
                    'period_length_days' => $avgPeriodLength ?? 5,
                    'confidence' => $confidence,
                    'phase' => $phase,
                    'days_until_period' => isset($predictions['next_period']) ? Carbon::now()->diffInDays(Carbon::parse($predictions['next_period']), false) : null,
                    'days_until_ovulation' => $ovulation ? Carbon::now()->diffInDays($ovulation, false) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payload
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load predictions'
            ], 500);
        }
    }

    /**
     * Get a single cycle by id (row-level ownership enforced)
     */
    public function getCycle($id)
    {
        $cycle = PeriodCycle::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$cycle) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cycle->id,
                'start_date' => $cycle->start_date,
                'end_date' => $cycle->end_date,
                'length' => $cycle->cycle_length ?? $this->calculateCycleLength($cycle),
                'flow_intensity' => $cycle->flow_intensity ?? 'Medium',
                'symptoms' => $this->getCycleSymptoms($cycle->id),
                'mood' => $cycle->mood ?? 'Normal',
                'notes' => $cycle->notes,
                'created_at' => $cycle->created_at->toISOString(),
                'updated_at' => $cycle->updated_at->toISOString()
            ]
        ]);
    }

    /**
     * Update a cycle (row-level ownership enforced)
     */
    public function updateCycle(Request $request, $id)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'flow_intensity' => 'nullable|in:light,medium,heavy,very_heavy',
            'symptoms' => 'nullable|array',
            'mood' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $cycle = PeriodCycle::where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$cycle) {
            return response()->json([
                'success' => false,
                'message' => 'Cycle not found'
            ], 404);
        }

        $cycle->start_date = $request->start_date ?? $cycle->start_date;
        $cycle->end_date = $request->end_date ?? $cycle->end_date;
        $cycle->flow_intensity = $request->flow_intensity ?? $cycle->flow_intensity;
        $cycle->mood = $request->mood ?? $cycle->mood;
        $cycle->notes = $request->notes ?? $cycle->notes;
        // Recalculate cycle_length if both dates present
        if ($cycle->start_date && $cycle->end_date) {
            $cycle->cycle_length = Carbon::parse($cycle->start_date)->diffInDays(Carbon::parse($cycle->end_date)) + 1;
        }
        $cycle->save();

        // Replace symptoms if provided
        if (is_array($request->symptoms)) {
            PeriodSymptom::where('cycle_id', $cycle->id)->where('user_id', auth()->id())->delete();
            foreach ($request->symptoms as $symptom) {
                PeriodSymptom::create([
                    'user_id' => auth()->id(),
                    'cycle_id' => $cycle->id,
                    'date' => $cycle->start_date,
                    'symptom' => $symptom,
                ]);
            }
        }

        // Invalidate prediction cache for this user
        Cache::forget("period_predictions_" . auth()->id());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cycle->id,
                'start_date' => $cycle->start_date,
                'end_date' => $cycle->end_date,
                'length' => $cycle->cycle_length ?? $this->calculateCycleLength($cycle),
                'flow_intensity' => $cycle->flow_intensity ?? 'Medium',
                'symptoms' => $this->getCycleSymptoms($cycle->id),
                'mood' => $cycle->mood ?? 'Normal',
                'notes' => $cycle->notes,
                'created_at' => $cycle->created_at->toISOString(),
                'updated_at' => $cycle->updated_at->toISOString()
            ]
        ]);
    }

    /**
     * Calculate ovulation date (typically 14 days before next period)
     */
    private function calculateOvulationDate($nextPeriodDate)
    {
        if (!$nextPeriodDate) {
            return null;
        }
        
        return Carbon::parse($nextPeriodDate)->subDays(14)->toDateString();
    }

    /**
     * Calculate prediction confidence based on data quality
     */
    private function calculatePredictionConfidence($cycles)
    {
        if ($cycles->count() < 2) {
            return 0.3; // Low confidence with minimal data
        }
        
        if ($cycles->count() < 6) {
            return 0.6; // Medium confidence with some data
        }
        
        // Calculate regularity for higher confidence
        $cycleLengths = [];
        for ($i = 0; $i < $cycles->count() - 1; $i++) {
            $current = Carbon::parse($cycles[$i]->start_date);
            $next = Carbon::parse($cycles[$i + 1]->start_date);
            $cycleLengths[] = $next->diffInDays($current);
        }
        
        if (count($cycleLengths) > 1) {
            $stdDev = $this->calculateStandardDeviation($cycleLengths);
            $regularity = max(0, 1 - ($stdDev / 10)); // Higher regularity = higher confidence
            return min(0.95, max(0.3, $regularity));
        }
        
        return 0.7; // Default confidence
    }
    
    /**
     * Delete a cycle
     */
    public function deleteCycle($cycleId)
    {
        try {
            $cycle = PeriodCycle::where('user_id', auth()->id())
                ->where('id', $cycleId)
                ->first();

            if (!$cycle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cycle not found'
                ], 404);
            }

            // Delete associated symptoms
            PeriodSymptom::where('cycle_id', $cycleId)->delete();
            
            // Delete the cycle
            $cycle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cycle deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cycle'
            ], 500);
        }
    }
    
    /**
     * Calculate cycle statistics
     */
    private function calculateCycleStatistics($cycles)
    {
        if ($cycles->count() < 2) {
            return [
                'average_cycle_length' => null,
                'average_period_length' => null,
                'regularity_percentage' => null,
                'total_cycles' => $cycles->count()
            ];
        }
        
        // Calculate cycle lengths
        $cycleLengths = [];
        for ($i = 0; $i < $cycles->count() - 1; $i++) {
            $current = Carbon::parse($cycles[$i]->start_date);
            $next = Carbon::parse($cycles[$i + 1]->start_date);
            $cycleLengths[] = $next->diffInDays($current);
        }
        
        $avgCycleLength = count($cycleLengths) > 0 ? round(array_sum($cycleLengths) / count($cycleLengths)) : null;
        
        // Calculate period lengths
        $periodLengths = $cycles->filter(function($cycle) {
            return $cycle->end_date !== null;
        })->map(function($cycle) {
            return Carbon::parse($cycle->start_date)->diffInDays(Carbon::parse($cycle->end_date)) + 1;
        });
        
        $avgPeriodLength = $periodLengths->count() > 0 ? round($periodLengths->avg()) : null;
        
        // Calculate regularity (how consistent cycle lengths are)
        $regularity = null;
        if (count($cycleLengths) > 1) {
            $stdDev = $this->calculateStandardDeviation($cycleLengths);
            $regularity = max(0, 100 - ($stdDev * 10)); // Simple regularity calculation
        }
        
        return [
            'average_cycle_length' => $avgCycleLength,
            'average_period_length' => $avgPeriodLength,
            'regularity_percentage' => $regularity ? round($regularity) : null,
            'total_cycles' => $cycles->count()
        ];
    }
    
    /**
     * Get current cycle phase
     */
    private function getCurrentCyclePhase($lastCycle, $statistics)
    {
        if (!$lastCycle || !$statistics['average_cycle_length']) {
            return [
                'phase' => 'tracking',
                'description' => 'Keep tracking to discover your cycle phase!',
                'day' => 1,
                'encouragement' => 'You\'re doing great! Log more cycles to get phase insights.',
                'estimated_next_period' => Carbon::now()->addDays(28)->toDateString(),
                'tracking_tip' => 'Track at least 2-3 cycles for accurate phase detection.'
            ];
        }
        
        $lastPeriodStart = Carbon::parse($lastCycle->start_date);
        $daysSinceLastPeriod = $lastPeriodStart->diffInDays(Carbon::now());
        $avgCycleLength = $statistics['average_cycle_length'];
        
        // Determine phase based on cycle day
        $cycleDay = ($daysSinceLastPeriod % $avgCycleLength) + 1;
        
        if ($cycleDay <= 5) {
            return [
                'phase' => 'Menstrual',
                'description' => 'Your period is likely occurring now',
                'day' => $cycleDay,
                'encouragement' => 'Take care of yourself during this phase!',
                'estimated_next_period' => $lastPeriodStart->addDays($avgCycleLength)->toDateString(),
                'tracking_tip' => 'Track your flow intensity and symptoms.'
            ];
        } elseif ($cycleDay <= 13) {
            return [
                'phase' => 'Follicular',
                'description' => 'Your body is preparing for ovulation',
                'day' => $cycleDay,
                'encouragement' => 'Great time for new beginnings and goals!',
                'estimated_next_period' => $lastPeriodStart->addDays($avgCycleLength)->toDateString(),
                'tracking_tip' => 'Track your energy levels and mood.'
            ];
        } elseif ($cycleDay <= 15) {
            return [
                'phase' => 'Ovulation',
                'description' => 'You are likely ovulating now',
                'day' => $cycleDay,
                'encouragement' => 'Peak energy and creativity time!',
                'estimated_next_period' => $lastPeriodStart->addDays($avgCycleLength)->toDateString(),
                'tracking_tip' => 'Perfect time for important decisions.'
            ];
        } else {
            return [
                'phase' => 'Luteal',
                'description' => 'Your body is preparing for the next cycle',
                'day' => $cycleDay,
                'encouragement' => 'Time for reflection and planning!',
                'estimated_next_period' => $lastPeriodStart->addDays($avgCycleLength)->toDateString(),
                'tracking_tip' => 'Focus on self-care and preparation.'
            ];
        }
    }
    
    /**
     * Generate predictions
     */
    private function generatePredictions($cycles, $statistics)
    {
        if (!$cycles->first() || !$statistics['average_cycle_length']) {
            return [
                'next_period' => null,
                'fertile_window' => null
            ];
        }
        
        $lastPeriodStart = Carbon::parse($cycles->first()->start_date);
        $avgCycleLength = $statistics['average_cycle_length'];
        
        // Predict next period
        $nextPeriod = $lastPeriodStart->copy()->addDays($avgCycleLength);
        
        // Predict fertile window (typically 5 days before ovulation + ovulation day)
        $ovulationDay = $nextPeriod->copy()->subDays(14); // Ovulation typically 14 days before next period
        $fertileStart = $ovulationDay->copy()->subDays(5);
        $fertileEnd = $ovulationDay->copy()->addDay();
        
        return [
            'next_period' => $nextPeriod->toDateString(),
            'fertile_window' => [
                'start' => $fertileStart->toDateString(),
                'end' => $fertileEnd->toDateString()
            ]
        ];
    }
    
    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation($values)
    {
        $count = count($values);
        if ($count <= 1) return 0;
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / ($count - 1);
        
        return sqrt($variance);
    }
    
    /**
     * Get available symptom types
     */
    private function getAvailableSymptomTypes()
    {
        return [
            'cramps' => ['name' => 'Cramps', 'category' => 'pain', 'icon' => '🤕', 'color' => '#ff6b6b'],
            'bloating' => ['name' => 'Bloating', 'category' => 'physical', 'icon' => '🤰', 'color' => '#4ecdc4'],
            'headache' => ['name' => 'Headache', 'category' => 'pain', 'icon' => '🤯', 'color' => '#ff9f43'],
            'mood_swings' => ['name' => 'Mood Swings', 'category' => 'emotional', 'icon' => '😤', 'color' => '#a55eea'],
            'anxiety' => ['name' => 'Anxiety', 'category' => 'emotional', 'icon' => '😰', 'color' => '#26d0ce'],
            'fatigue' => ['name' => 'Fatigue', 'category' => 'energy', 'icon' => '😴', 'color' => '#fed330'],
            'acne' => ['name' => 'Acne', 'category' => 'skin', 'icon' => '😓', 'color' => '#fd79a8'],
            'breast_tenderness' => ['name' => 'Breast Tenderness', 'category' => 'physical', 'icon' => '🤲', 'color' => '#fdcb6e'],
            'back_pain' => ['name' => 'Back Pain', 'category' => 'pain', 'icon' => '🦴', 'color' => '#e17055'],
            'nausea' => ['name' => 'Nausea', 'category' => 'digestive', 'icon' => '🤢', 'color' => '#00b894'],
            'insomnia' => ['name' => 'Insomnia', 'category' => 'sleep', 'icon' => '😵', 'color' => '#6c5ce7'],
            'food_cravings' => ['name' => 'Food Cravings', 'category' => 'appetite', 'icon' => '🍫', 'color' => '#a29bfe']
        ];
    }

    /**
     * Get analytics data
     */
    public function getAnalytics()
    {
        try {
            $user = auth()->user();
            
            // Get cycles for analysis
            $cycles = PeriodCycle::where('user_id', $user->id)
                ->where('is_predicted', false)
                ->orderBy('start_date', 'desc')
                ->limit(12)
                ->get();

            // Get symptoms for analysis
            $symptoms = PeriodSymptom::where('user_id', $user->id)
                ->where('date', '>=', Carbon::now()->subMonths(6))
                ->get();

            // Check if user has minimum data
            $hasMinimumData = $cycles->count() >= 2;

            if (!$hasMinimumData) {
                // Provide encouraging analytics for new users
                $analytics = [
                    'average_cycle_length' => 28, // Standard cycle length
                    'cycle_variability' => 'tracking',
                    'common_symptoms' => [
                        ['symptom' => 'Cramps', 'frequency' => 0],
                        ['symptom' => 'Fatigue', 'frequency' => 0],
                        ['symptom' => 'Headache', 'frequency' => 0]
                    ],
                    'mood_patterns' => [
                        ['mood' => 'Normal', 'frequency' => 0],
                        ['mood' => 'Happy', 'frequency' => 0],
                        ['mood' => 'Sad', 'frequency' => 0]
                    ],
                    'insights' => [
                        'Start tracking your cycles to get personalized insights!',
                        'Log at least 2-3 cycles for accurate analytics.',
                        'Track symptoms to understand your patterns.',
                        'Your data will become more accurate as you track more cycles.'
                    ],
                    'tracking_progress' => [
                        'cycles_logged' => $cycles->count(),
                        'cycles_needed' => max(0, 3 - $cycles->count()),
                        'progress_percentage' => min(100, ($cycles->count() / 3) * 100),
                        'message' => $cycles->count() >= 3 
                            ? 'Great! You have enough data for accurate analytics.' 
                            : 'Log ' . (3 - $cycles->count()) . ' more cycle(s) for better insights.'
                    ],
                    'encouragement' => 'You\'re doing great! Keep tracking to unlock detailed analytics.'
                ];
            } else {
                // Calculate analytics for users with sufficient data
                $analytics = [
                    'average_cycle_length' => $this->calculateAverageCycleLength($cycles),
                    'cycle_variability' => $this->calculateCycleVariability($cycles),
                    'common_symptoms' => $this->getCommonSymptoms($symptoms),
                    'mood_patterns' => $this->getMoodPatterns($cycles),
                    'insights' => $this->generateInsights($cycles, $symptoms)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load analytics'
            ], 500);
        }
    }

    /**
     * Calculate average cycle length
     */
    private function calculateAverageCycleLength($cycles)
    {
        if ($cycles->count() < 2) {
            return null;
        }

        $cycleLengths = [];
        for ($i = 0; $i < $cycles->count() - 1; $i++) {
            $current = Carbon::parse($cycles[$i]->start_date);
            $next = Carbon::parse($cycles[$i + 1]->start_date);
            $cycleLengths[] = $next->diffInDays($current);
        }

        return count($cycleLengths) > 0 ? round(array_sum($cycleLengths) / count($cycleLengths), 1) : null;
    }

    /**
     * Calculate cycle variability
     */
    private function calculateCycleVariability($cycles)
    {
        if ($cycles->count() < 3) {
            return null;
        }

        $cycleLengths = [];
        for ($i = 0; $i < $cycles->count() - 1; $i++) {
            $current = Carbon::parse($cycles[$i]->start_date);
            $next = Carbon::parse($cycles[$i + 1]->start_date);
            $cycleLengths[] = $next->diffInDays($current);
        }

        if (count($cycleLengths) > 1) {
            $stdDev = $this->calculateStandardDeviation($cycleLengths);
            return round($stdDev, 1);
        }

        return null;
    }

    /**
     * Get common symptoms
     */
    private function getCommonSymptoms($symptoms)
    {
        $symptomCounts = $symptoms->groupBy('symptom')
            ->map(function($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(5);

        return $symptomCounts->map(function($frequency, $symptom) {
            return [
                'symptom' => $symptom,
                'frequency' => $frequency
            ];
        })->values()->toArray();
    }

    /**
     * Get mood patterns
     */
    private function getMoodPatterns($cycles)
    {
        $moodCounts = $cycles->whereNotNull('mood')
            ->groupBy('mood')
            ->map(function($group) {
                return $group->count();
            })
            ->sortDesc()
            ->take(5);

        return $moodCounts->map(function($frequency, $mood) {
            return [
                'mood' => $mood,
                'frequency' => $frequency
            ];
        })->values()->toArray();
    }

    /**
     * Generate insights
     */
    private function generateInsights($cycles, $symptoms)
    {
        $insights = [];

        // Cycle length insight
        $avgCycleLength = $this->calculateAverageCycleLength($cycles);
        if ($avgCycleLength) {
            $insights[] = "Your average cycle length is {$avgCycleLength} days";
        }

        // Cycle regularity insight
        $variability = $this->calculateCycleVariability($cycles);
        if ($variability) {
            if ($variability <= 2) {
                $insights[] = "You have a regular cycle pattern with low variability";
            } elseif ($variability <= 5) {
                $insights[] = "Your cycle shows moderate variability";
            } else {
                $insights[] = "Your cycle shows high variability - consider tracking more consistently";
            }
        }

        // Symptom insights
        $commonSymptoms = $this->getCommonSymptoms($symptoms);
        if (!empty($commonSymptoms)) {
            $topSymptom = $commonSymptoms[0];
            $insights[] = "You typically experience {$topSymptom['symptom']} on days 1-3";
        }

        // Mood insights
        $moodPatterns = $this->getMoodPatterns($cycles);
        if (!empty($moodPatterns)) {
            $topMood = $moodPatterns[0];
            $insights[] = "Your mood tends to be {$topMood['mood']} during your period";
        }

        // Add default insight if not enough data
        if (empty($insights)) {
            $insights[] = "Track more cycles to get personalized insights";
        }

        return $insights;
    }

    /**
     * Debug method to log request data
     */
    public function debugRequest(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'all_data' => $request->all(),
                'headers' => $request->headers->all(),
                'method' => $request->method(),
                'url' => $request->url(),
                'user' => auth()->user() ? auth()->user()->id : null
            ]
        ]);
    }

    /**
     * Test endpoint to check API functionality
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'Period tracker API is working',
            'timestamp' => now()->toISOString(),
            'user' => auth()->user() ? auth()->user()->id : null,
            'routes' => [
                'cycles' => '/api/period-tracker/cycles',
                'symptoms' => '/api/period-tracker/symptoms',
                'predictions' => '/api/period-tracker/predictions'
            ]
        ]);
    }

    /**
     * Handle period data with date field (frontend format)
     */
    public function handlePeriodData(Request $request)
    {
        try {
            // Log the incoming request data
            \Log::info('Period data request', [
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            $request->validate([
                'date' => 'required|date',
                'flow_intensity' => 'nullable|string',
                'symptoms' => 'nullable|array',
                'mood' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $user = auth()->user();
            $date = $request->date;

            // Create cycle entry
            $cycle = PeriodCycle::create([
                'user_id' => $user->id,
                'start_date' => $date,
                'end_date' => $date, // Single day cycle
                'cycle_length' => 1,
                'flow_intensity' => $request->flow_intensity ?? 'Medium',
                'mood' => $request->mood ?? 'Normal',
                'notes' => $request->notes,
            ]);

            // Create symptoms if provided
            $symptomIds = [];
            if ($request->symptoms && is_array($request->symptoms)) {
                foreach ($request->symptoms as $symptom) {
                    $symptomRecord = PeriodSymptom::create([
                        'user_id' => $user->id,
                        'cycle_id' => $cycle->id,
                        'date' => $date,
                        'symptom' => $symptom,
                        'flow_intensity' => $request->flow_intensity,
                        'mood' => $request->mood,
                        'notes' => $request->notes,
                    ]);
                    $symptomIds[] = $symptomRecord->id;
                }
            }

            // Return the created data
            $responseData = [
                'cycle' => [
                    'id' => $cycle->id,
                    'start_date' => $cycle->start_date,
                    'end_date' => $cycle->end_date,
                    'length' => $cycle->cycle_length,
                    'flow_intensity' => $cycle->flow_intensity,
                    'symptoms' => $request->symptoms ?? [],
                    'mood' => $cycle->mood,
                    'notes' => $cycle->notes,
                    'created_at' => $cycle->created_at->toISOString(),
                    'updated_at' => $cycle->updated_at->toISOString()
                ],
                'symptoms' => $symptomIds
            ];

            // Broadcast real-time update
            event(new PeriodDataUpdated($user->id, $responseData, 'period_data'));

            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save period data: ' . $e->getMessage()
            ], 500);
        }
    }
}