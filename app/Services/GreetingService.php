<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Greeting Service
 * 
 * Handles timezone-based greeting calculations and user preferences
 * with caching and fallback mechanisms.
 */
class GreetingService
{
    /**
     * Get personalized greeting for user
     * 
     * @param User $user
     * @return array
     */
    public function getPersonalizedGreeting(User $user): array
    {
        try {
            $cacheKey = "greeting_{$user->id}_" . Carbon::now()->format('Y-m-d-H');
            
            return Cache::remember($cacheKey, 3600, function () use ($user) { // 1 hour cache
                $userTime = $this->getUserLocalTime($user);
                $greeting = $this->calculateGreeting($userTime);
                $mood = $this->getMoodBasedGreeting($user);
                
                return [
                    'greeting' => $greeting,
                    'mood' => $mood,
                    'user_time' => $userTime->format('h:i A'),
                    'user_date' => $userTime->format('l, F j, Y'),
                    'timezone' => $user->timezone ?? config('app.timezone'),
                    'personalized_message' => $this->getPersonalizedMessage($user, $greeting),
                    'generated_at' => now()->toISOString()
                ];
            });

        } catch (\Exception $e) {
            return $this->getFallbackGreeting();
        }
    }

    /**
     * Calculate greeting based on time of day
     * 
     * @param Carbon $userTime
     * @return string
     */
    private function calculateGreeting(Carbon $userTime): string
    {
        $hour = $userTime->hour;

        if ($hour >= 5 && $hour < 12) {
            return 'Good Morning';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'Good Afternoon';
        } elseif ($hour >= 17 && $hour < 21) {
            return 'Good Evening';
        } else {
            return 'Good Night';
        }
    }

    /**
     * Get user's local time based on timezone
     * 
     * @param User $user
     * @return Carbon
     */
    private function getUserLocalTime(User $user): Carbon
    {
        $timezone = $user->timezone ?? config('app.timezone');
        return Carbon::now()->timezone($timezone);
    }

    /**
     * Get mood-based greeting
     * 
     * @param User $user
     * @return string
     */
    private function getMoodBasedGreeting(User $user): string
    {
        // This could be enhanced with user mood tracking
        $moods = [
            'energetic' => 'Hope you\'re feeling energetic today!',
            'calm' => 'Wishing you a peaceful day ahead.',
            'focused' => 'Stay focused and productive!',
            'relaxed' => 'Take time to relax and recharge.',
            'motivated' => 'You\'ve got this! Stay motivated.',
            'grateful' => 'Grateful for another beautiful day.'
        ];

        // For now, return a random mood - this could be enhanced with user preferences
        return $moods[array_rand($moods)];
    }

    /**
     * Get personalized message based on user data
     * 
     * @param User $user
     * @param string $greeting
     * @return string
     */
    private function getPersonalizedMessage(User $user, string $greeting): string
    {
        $messages = [
            'Good Morning' => [
                'Have a wonderful start to your day!',
                'Ready to conquer the day ahead?',
                'A new day brings new opportunities!',
                'Start your day with positivity!'
            ],
            'Good Afternoon' => [
                'Hope your day is going great!',
                'Keep up the good work!',
                'You\'re doing amazing!',
                'Stay hydrated and energized!'
            ],
            'Good Evening' => [
                'Hope you had a productive day!',
                'Time to unwind and relax!',
                'You\'ve accomplished so much today!',
                'Enjoy your evening!'
            ],
            'Good Night' => [
                'Rest well and dream big!',
                'Tomorrow is another opportunity!',
                'Sleep tight and recharge!',
                'Sweet dreams!'
            ]
        ];

        $greetingMessages = $messages[$greeting] ?? ['Have a great day!'];
        return $greetingMessages[array_rand($greetingMessages)];
    }

    /**
     * Get fallback greeting when errors occur
     * 
     * @return array
     */
    private function getFallbackGreeting(): array
    {
        return [
            'greeting' => 'Hello',
            'mood' => 'Hope you\'re having a great day!',
            'user_time' => Carbon::now()->format('h:i A'),
            'user_date' => Carbon::now()->format('l, F j, Y'),
            'timezone' => config('app.timezone'),
            'personalized_message' => 'Welcome back!',
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Get greeting with period tracking context
     * 
     * @param User $user
     * @return array
     */
    public function getGreetingWithPeriodContext(User $user): array
    {
        $baseGreeting = $this->getPersonalizedGreeting($user);
        
        // Add period tracking context if available
        $periodContext = $this->getPeriodContext($user);
        
        return array_merge($baseGreeting, [
            'period_context' => $periodContext
        ]);
    }

    /**
     * Get period tracking context for greeting
     * 
     * @param User $user
     * @return array
     */
    private function getPeriodContext(User $user): array
    {
        try {
            // Integrate with PeriodTrackerService
            $periodTrackerService = app(\App\Services\PeriodTrackerService::class);
            return $periodTrackerService->getPeriodContextForGreeting($user);
        } catch (\Exception $e) {
            return [
                'has_period_data' => false,
                'current_phase' => null,
                'days_until_next' => null,
                'message' => 'Welcome to your health journey!',
                'suggestion' => 'Start tracking to get personalized insights.'
            ];
        }
    }
} 