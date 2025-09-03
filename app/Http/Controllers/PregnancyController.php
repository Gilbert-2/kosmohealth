<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PregnancyController extends Controller
{
    /**
     * Get pregnancy overview
     * GET /api/pregnancy/overview
     */
    public function getOverview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'current_week' => 12,
                'current_day' => 84,
                'due_date' => '2025-01-15',
                'days_until_due' => 196,
                'trimester' => 'First',
                'pregnancy_progress' => 30,
                'baby_size' => 'Lime',
                'current_status' => [
                    'status' => 'active',
                    'gestational_age_weeks' => 12,
                    'gestational_age_days' => 84
                ]
            ]
        ]);
    }

    /**
     * Get baby development
     * GET /api/pregnancy/development
     */
    public function getDevelopment(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'current_week' => 12,
                'milestones' => [
                    'Baby is about the size of a lime',
                    'All major organs are formed',
                    'Baby can make sucking motions',
                    'Fingernails and toenails are forming'
                ],
                'baby_size' => [
                    'size' => 'Lime',
                    'length' => 4.5,
                    'weight' => 4.5
                ]
            ]
        ]);
    }

    /**
     * Get appointments
     * GET /api/pregnancy/appointments
     */
    public function getAppointments(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Get symptoms
     * GET /api/pregnancy/symptoms
     */
    public function getSymptoms(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Get health metrics
     * GET /api/pregnancy/health-metrics
     */
    public function getHealthMetrics(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Get timeline
     * GET /api/pregnancy/timeline
     */
    public function getTimeline(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'current_week' => 12,
                'timeline' => [
                    [
                        'week' => 10,
                        'gestational_age' => '10 weeks',
                        'baby_size' => 'Kumquat',
                        'baby_weight' => '2.5 grams',
                        'key_developments' => ['Fingernails and toenails form'],
                        'mother_changes' => ['No visible changes yet'],
                        'recommendations' => ['Continue prenatal vitamins']
                    ],
                    [
                        'week' => 11,
                        'gestational_age' => '11 weeks',
                        'baby_size' => 'Fig',
                        'baby_weight' => '3.5 grams',
                        'key_developments' => ['Baby can make sucking motions'],
                        'mother_changes' => ['No visible changes yet'],
                        'recommendations' => ['Continue prenatal vitamins']
                    ],
                    [
                        'week' => 12,
                        'gestational_age' => '12 weeks',
                        'baby_size' => 'Lime',
                        'baby_weight' => '4.5 grams',
                        'key_developments' => ['Sex can be determined'],
                        'mother_changes' => ['No visible changes yet'],
                        'recommendations' => ['Continue prenatal vitamins']
                    ]
                ]
            ]
        ]);
    }
} 