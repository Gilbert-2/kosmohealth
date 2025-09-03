<?php

namespace App\Http\Controllers;

use App\Services\GreetingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Greeting Controller
 * 
 * Handles personalized greeting requests with timezone support
 * and period tracking context integration.
 */
class GreetingController extends Controller
{
    protected GreetingService $greetingService;

    public function __construct(GreetingService $greetingService)
    {
        $this->greetingService = $greetingService;
    }

    /**
     * Get personalized greeting
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getGreeting(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $greeting = $this->greetingService->getPersonalizedGreeting($user);

            return response()->json([
                'success' => true,
                'data' => $greeting
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate greeting'
            ], 500);
        }
    }

    /**
     * Get greeting with period tracking context
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getGreetingWithPeriodContext(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $greeting = $this->greetingService->getGreetingWithPeriodContext($user);

            return response()->json([
                'success' => true,
                'data' => $greeting
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate greeting with period context'
            ], 500);
        }
    }

    /**
     * Get greeting for dashboard
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardGreeting(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $greeting = $this->greetingService->getGreetingWithPeriodContext($user);

            // Add dashboard-specific context
            $dashboardGreeting = array_merge($greeting, [
                'dashboard_context' => [
                    'welcome_message' => 'Welcome to your health dashboard!',
                    'quick_actions' => [
                        'track_period' => 'Log your period',
                        'track_symptoms' => 'Track symptoms',
                        'view_calendar' => 'View calendar',
                        'check_predictions' => 'Check predictions'
                    ],
                    'last_updated' => now()->toISOString()
                ],
                // Flat quickActions array for Quick Log UI
                'quickActions' => [
                    [ 'key' => 'log_period', 'label' => 'Log Period' ],
                    [ 'key' => 'log_symptoms', 'label' => 'Log Symptoms' ],
                    [ 'key' => 'open_calendar', 'label' => 'Open Calendar' ],
                    [ 'key' => 'view_predictions', 'label' => 'View Predictions' ]
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => $dashboardGreeting
            ]);

        } catch (\Exception $e) {
            \Log::error('Greeting API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate dashboard greeting',
                'fallback' => [
                    'greeting' => 'Hello',
                    'mood' => 'Welcome to your health dashboard!',
                    'user_time' => now()->format('h:i A'),
                    'user_date' => now()->format('l, F j, Y'),
                    'timezone' => config('app.timezone'),
                    'personalized_message' => 'Welcome back!',
                    'period_context' => [
                        'has_period_data' => false,
                        'message' => 'Start tracking your health journey!'
                    ],
                    'dashboard_context' => [
                        'welcome_message' => 'Welcome to your health dashboard!',
                        'quick_actions' => [
                            'track_period' => 'Log your period',
                            'track_symptoms' => 'Track symptoms',
                            'view_calendar' => 'View calendar',
                            'check_predictions' => 'Check predictions'
                        ]
                    ]
                ]
            ], 500);
        }
    }
} 