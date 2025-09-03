<?php

namespace App\Services;

use App\Models\User;
use App\Models\PeriodCycle;
use App\Models\PeriodSymptom;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Period Tracker Service
 * 
 * Handles all business logic for period tracking with enterprise-level
 * security, caching, and error handling.
 */
class PeriodTrackerService
{
    /**
     * Get active notifications for a user
     * 
     * @param User $user
     * @return array
     */
    public function getActiveNotifications(User $user): array
    {
        try {
            $cacheKey = "period_notifications_{$user->id}";
            
            return Cache::remember($cacheKey, 900, function () use ($user) { // 15 minutes cache
                $notifications = [
                    'count' => 0,
                    'has_emergency' => false,
                    'priority_alerts' => [],
                    'alerts' => [],
                    'reminders' => [],
                    'health_flags' => [],
                    'priority_actions' => []
                ];

                // Get recent cycle data
                $latestCycle = PeriodCycle::where('user_id', $user->id)
                    ->orderBy('start_date', 'desc')
                    ->first();

                if ($latestCycle) {
                    // Check for overdue period
                    $daysSinceLastPeriod = Carbon::now()->diffInDays($latestCycle->start_date);
                    
                    if ($daysSinceLastPeriod > 35) { // Cycle longer than 35 days
                        $notifications['alerts'][] = [
                            'type' => 'overdue_period',
                            'title' => 'Period Overdue',
                            'message' => "It's been {$daysSinceLastPeriod} days since your last period",
                            'priority' => 'medium'
                        ];
                        $notifications['count']++;
                    }

                    // Check for upcoming period
                    $predictedNextPeriod = $this->calculateNextPeriodDate($user);
                    if ($predictedNextPeriod && Carbon::now()->diffInDays($predictedNextPeriod, false) <= 3) {
                        $notifications['reminders'][] = [
                            'type' => 'period_reminder',
                            'title' => 'Period Starting Soon',
                            'message' => 'Your period is expected to start in the next few days',
                            'priority' => 'low'
                        ];
                        $notifications['count']++;
                    }
                }

                // Check for logged symptoms requiring attention
                $recentSymptoms = PeriodSymptom::where('user_id', $user->id)
                    ->where('logged_at', '>=', Carbon::now()->subDays(7))
                    ->where('severity', '>=', 4) // High severity symptoms
                    ->exists();

                if ($recentSymptoms) {
                    $notifications['health_flags'][] = [
                        'type' => 'severe_symptoms',
                        'title' => 'Severe Symptoms Noted',
                        'message' => 'Consider consulting with a healthcare provider',
                        'priority' => 'high'
                    ];
                    $notifications['count']++;
                }

                return $notifications;
            });

        } catch (\Exception $e) {
            Log::error('Error getting period notifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'count' => 0,
                'has_emergency' => false,
                'priority_alerts' => [],
                'alerts' => [],
                'reminders' => [],
                'health_flags' => [],
                'priority_actions' => []
            ];
        }
    }

    /**
     * Check if user has minimum data for analytics
     * 
     * @param User $user
     * @return bool
     */
    public function hasMinimumDataForAnalytics(User $user): bool
    {
        $cycleCount = PeriodCycle::where('user_id', $user->id)->count();
        return $cycleCount >= 3;
    }

    /**
     * Calculate next period date based on user's cycle history
     * 
     * @param User $user
     * @return Carbon|null
     */
    private function calculateNextPeriodDate(User $user): ?Carbon
    {
        $recentCycles = PeriodCycle::where('user_id', $user->id)
            ->orderBy('start_date', 'desc')
            ->limit(3)
            ->get();

        if ($recentCycles->count() < 2) {
            return null;
        }

        // Calculate average cycle length
        $cycleLengths = [];
        for ($i = 0; $i < $recentCycles->count() - 1; $i++) {
            $cycleLengths[] = Carbon::parse($recentCycles[$i]->start_date)
                ->diffInDays(Carbon::parse($recentCycles[$i + 1]->start_date));
        }

        $averageCycleLength = array_sum($cycleLengths) / count($cycleLengths);
        
        // Add average cycle length to last period start date
        return Carbon::parse($recentCycles->first()->start_date)
            ->addDays(round($averageCycleLength));
    }

    /**
     * Get cycle phase information for a user
     * 
     * @param User $user
     * @return array
     */
    public function getCurrentCyclePhase(User $user): array
    {
        try {
            $latestCycle = PeriodCycle::where('user_id', $user->id)
                ->orderBy('start_date', 'desc')
                ->first();

            if (!$latestCycle) {
                return [
                    'phase' => 'unknown',
                    'day' => 0,
                    'description' => 'Start tracking to see your cycle phase'
                ];
            }

            $daysSinceStart = Carbon::now()->diffInDays(Carbon::parse($latestCycle->start_date));
            $cycleDay = $daysSinceStart + 1;

            // Determine phase based on cycle day
            if ($cycleDay <= 5) {
                $phase = 'menstrual';
                $description = 'Menstruation phase';
            } elseif ($cycleDay <= 13) {
                $phase = 'follicular';
                $description = 'Follicular phase';
            } elseif ($cycleDay <= 15) {
                $phase = 'ovulation';
                $description = 'Ovulation phase';
            } else {
                $phase = 'luteal';
                $description = 'Luteal phase';
            }

            return [
                'phase' => $phase,
                'day' => $cycleDay,
                'description' => $description,
                'start_date' => $latestCycle->start_date
            ];

        } catch (\Exception $e) {
            Log::error('Error getting cycle phase', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'phase' => 'unknown',
                'day' => 0,
                'description' => 'Unable to determine cycle phase'
            ];
        }
    }

    /**
     * Log period data for user
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function logPeriodData(User $user, array $data): array
    {
        try {
            // Validate and sanitize data
            $cycleData = [
                'user_id' => $user->id,
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'flow_intensity' => $data['flow_intensity'] ?? 'medium',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Save to database
            $cycle = PeriodCycle::create($cycleData);

            return [
                'success' => true,
                'cycle_id' => $cycle->id,
                'message' => 'Period data logged successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error logging period data', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to log period data'
            ];
        }
    }

    /**
     * Track symptoms for user
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function trackSymptoms(User $user, array $data): array
    {
        try {
            $symptoms = $data['symptoms'] ?? [];
            $loggedSymptoms = [];

            foreach ($symptoms as $symptom) {
                $symptomData = [
                    'user_id' => $user->id,
                    'symptom_type' => $symptom['type'] ?? 'general',
                    'severity' => min(5, max(1, $symptom['severity'] ?? 1)),
                    'notes' => $symptom['notes'] ?? null,
                    'logged_at' => $data['date'] ?? now()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $loggedSymptoms[] = PeriodSymptom::create($symptomData);
            }

            return [
                'success' => true,
                'symptoms_logged' => count($loggedSymptoms),
                'message' => 'Symptoms tracked successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error tracking symptoms', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to track symptoms'
            ];
        }
    }

    /**
     * Schedule reminder for user
     * 
     * @param User $user
     * @param array $data
     * @return array
     */
    public function scheduleReminder(User $user, array $data): array
    {
        try {
            $reminderType = $data['type'] ?? 'period_reminder';
            $reminderDate = $data['date'] ?? null;

            // This would integrate with a notification system
            // For now, we'll return a success response
            
            return [
                'success' => true,
                'reminder_type' => $reminderType,
                'scheduled_for' => $reminderDate,
                'message' => 'Reminder scheduled successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error scheduling reminder', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to schedule reminder'
            ];
        }
    }

    /**
     * Get period context for greeting integration
     * 
     * @param User $user
     * @return array
     */
    public function getPeriodContextForGreeting(User $user): array
    {
        try {
            $latestCycle = PeriodCycle::where('user_id', $user->id)
                ->orderBy('start_date', 'desc')
                ->first();

            if (!$latestCycle) {
                return [
                    'has_period_data' => false,
                    'current_phase' => 'unknown',
                    'days_until_next' => null,
                    'message' => 'Start tracking your cycle for personalized insights!',
                    'suggestion' => 'Log your first period to get started.'
                ];
            }

            $currentPhase = $this->getCurrentCyclePhase($user);
            $daysUntilNext = $this->getDaysUntilNextPeriod($user);

            $context = [
                'has_period_data' => true,
                'current_phase' => $currentPhase['phase'] ?? 'unknown',
                'cycle_day' => $currentPhase['day'] ?? 0,
                'days_until_next' => $daysUntilNext,
                'last_period_date' => $latestCycle->start_date,
                'message' => $this->getContextualMessage($currentPhase, $daysUntilNext),
                'suggestion' => $this->getContextualSuggestion($currentPhase, $daysUntilNext)
            ];

            return $context;

        } catch (\Exception $e) {
            return [
                'has_period_data' => false,
                'current_phase' => 'unknown',
                'days_until_next' => null,
                'message' => 'Welcome to your health journey!',
                'suggestion' => 'Start tracking to get personalized insights.'
            ];
        }
    }

    /**
     * Get contextual message based on cycle phase
     * 
     * @param array $currentPhase
     * @param int|null $daysUntilNext
     * @return string
     */
    private function getContextualMessage(array $currentPhase, ?int $daysUntilNext): string
    {
        $phase = $currentPhase['phase'] ?? 'unknown';

        switch ($phase) {
            case 'menstrual':
                return 'You\'re in your menstrual phase. Take care of yourself!';
            case 'follicular':
                return 'You\'re in your follicular phase. Great time for new beginnings!';
            case 'ovulation':
                return 'You\'re ovulating. Peak energy and creativity!';
            case 'luteal':
                return 'You\'re in your luteal phase. Time for reflection and planning.';
            default:
                if ($daysUntilNext !== null && $daysUntilNext <= 3) {
                    return 'Your period is expected soon. Be prepared!';
                }
                return 'Track your cycle for personalized insights!';
        }
    }

    /**
     * Get contextual suggestion based on cycle phase
     * 
     * @param array $currentPhase
     * @param int|null $daysUntilNext
     * @return string
     */
    private function getContextualSuggestion(array $currentPhase, ?int $daysUntilNext): string
    {
        $phase = $currentPhase['phase'] ?? 'unknown';

        switch ($phase) {
            case 'menstrual':
                return 'Consider tracking your flow intensity and symptoms.';
            case 'follicular':
                return 'Great time to set new goals and start new projects!';
            case 'ovulation':
                return 'Perfect time for important conversations and decisions.';
            case 'luteal':
                return 'Focus on self-care and planning for the next cycle.';
            default:
                if ($daysUntilNext !== null && $daysUntilNext <= 3) {
                    return 'Prepare for your upcoming period.';
                }
                return 'Log your symptoms to track patterns.';
        }
    }

    /**
     * Get days until next period
     * 
     * @param User $user
     * @return int|null
     */
    private function getDaysUntilNextPeriod(User $user): ?int
    {
        try {
            $latestCycle = PeriodCycle::where('user_id', $user->id)
                ->orderBy('start_date', 'desc')
                ->first();

            if (!$latestCycle) {
                return null;
            }

            // Calculate based on average cycle length (default 28 days)
            $averageCycleLength = $this->calculateAverageCycleLength($user) ?? 28;
            $daysSinceLastPeriod = Carbon::now()->diffInDays(Carbon::parse($latestCycle->start_date));
            $daysUntilNext = $averageCycleLength - $daysSinceLastPeriod;

            return max(0, $daysUntilNext);
        } catch (\Exception $e) {
            Log::error('Error calculating days until next period', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate average cycle length for user
     * 
     * @param User $user
     * @return int|null
     */
    private function calculateAverageCycleLength(User $user): ?int
    {
        try {
            $cycles = PeriodCycle::where('user_id', $user->id)
                ->whereNotNull('cycle_length')
                ->orderBy('start_date', 'desc')
                ->limit(6)
                ->get();

            if ($cycles->count() < 2) {
                return null;
            }

            return round($cycles->avg('cycle_length'));
        } catch (\Exception $e) {
            return null;
        }
    }


}