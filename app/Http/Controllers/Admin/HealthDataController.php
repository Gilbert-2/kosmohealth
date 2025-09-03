<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodCycle;
use App\Models\PeriodSymptom;
use App\Models\PregnancyRecord;
use App\Models\PregnancySymptom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthDataController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    /**
     * Get health data overview statistics
     * GET /api/admin/health-data/overview
     */
    public function overview(): JsonResponse
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active_period_trackers' => User::whereHas('periodCycles')->count(),
                    'active_pregnancy_trackers' => User::whereHas('pregnancyRecords')->count(),
                    'new_this_month' => User::where('created_at', '>=', Carbon::now()->startOfMonth())->count()
                ],
                'period_data' => [
                    'total_cycles' => PeriodCycle::count(),
                    'cycles_this_month' => PeriodCycle::where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
                    'total_symptoms' => PeriodSymptom::count(),
                    'avg_cycle_length' => PeriodCycle::whereNotNull('cycle_length')->avg('cycle_length')
                ],
                'pregnancy_data' => [
                    'total_pregnancies' => PregnancyRecord::count(),
                    'active_pregnancies' => PregnancyRecord::where('status', 'active')->count(),
                    'completed_pregnancies' => PregnancyRecord::where('status', 'completed')->count(),
                    'total_symptoms' => PregnancySymptom::count()
                ],
                'recent_activity' => [
                    'period_cycles_today' => PeriodCycle::whereDate('created_at', Carbon::today())->count(),
                    'pregnancy_symptoms_today' => PregnancySymptom::whereDate('created_at', Carbon::today())->count(),
                    'new_users_today' => User::whereDate('created_at', Carbon::today())->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Admin health data overview error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load health data overview'
            ], 500);
        }
    }

    /**
     * Get period cycles with pagination and filters
     * GET /api/admin/health-data/period-cycles
     */
    public function periodCycles(Request $request): JsonResponse
    {
        try {
            $query = PeriodCycle::with(['user:id,name,email'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('date_from')) {
                $query->where('start_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('start_date', '<=', $request->date_to);
            }

            if ($request->has('is_predicted')) {
                $query->where('is_predicted', $request->boolean('is_predicted'));
            }

            $cycles = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $cycles
            ]);

        } catch (\Exception $e) {
            Log::error('Admin period cycles error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load period cycles'
            ], 500);
        }
    }

    /**
     * Get pregnancy records with pagination and filters
     * GET /api/admin/health-data/pregnancy-records
     */
    public function pregnancyRecords(Request $request): JsonResponse
    {
        try {
            $query = PregnancyRecord::with(['user:id,name,email'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('trimester')) {
                $query->where('trimester', $request->trimester);
            }

            if ($request->has('due_date_from')) {
                $query->where('due_date', '>=', $request->due_date_from);
            }

            if ($request->has('due_date_to')) {
                $query->where('due_date', '<=', $request->due_date_to);
            }

            $pregnancies = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $pregnancies
            ]);

        } catch (\Exception $e) {
            Log::error('Admin pregnancy records error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load pregnancy records'
            ], 500);
        }
    }

    /**
     * Get health analytics for admin dashboard
     * GET /api/admin/health-data/analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($dateRange);

            $analytics = [
                'period_trends' => [
                    'daily_cycles' => PeriodCycle::where('created_at', '>=', $startDate)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get(),
                    'common_symptoms' => PeriodSymptom::where('created_at', '>=', $startDate)
                        ->selectRaw('symptom, COUNT(*) as count')
                        ->groupBy('symptom')
                        ->orderByDesc('count')
                        ->limit(10)
                        ->get(),
                    'avg_cycle_length' => PeriodCycle::where('created_at', '>=', $startDate)
                        ->whereNotNull('cycle_length')
                        ->avg('cycle_length')
                ],
                'pregnancy_trends' => [
                    'new_pregnancies' => PregnancyRecord::where('created_at', '>=', $startDate)
                        ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get(),
                    'trimester_distribution' => PregnancyRecord::where('status', 'active')
                        ->selectRaw('trimester, COUNT(*) as count')
                        ->groupBy('trimester')
                        ->get(),
                    'common_pregnancy_symptoms' => PregnancySymptom::where('created_at', '>=', $startDate)
                        ->selectRaw('symptom_type, COUNT(*) as count')
                        ->groupBy('symptom_type')
                        ->orderByDesc('count')
                        ->limit(10)
                        ->get()
                ],
                'user_engagement' => [
                    'active_users' => User::whereHas('periodCycles', function($q) use ($startDate) {
                        $q->where('created_at', '>=', $startDate);
                    })->orWhereHas('pregnancyRecords', function($q) use ($startDate) {
                        $q->where('created_at', '>=', $startDate);
                    })->count(),
                    'new_registrations' => User::where('created_at', '>=', $startDate)->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Admin health analytics error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load health analytics'
            ], 500);
        }
    }

    /**
     * Export health data (anonymized)
     * POST /api/admin/health-data/export
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:period_cycles,pregnancy_records,symptoms',
            'format' => 'required|in:json,csv',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'anonymize' => 'boolean'
        ]);

        try {
            $anonymize = $request->boolean('anonymize', true);
            $type = $request->type;
            $format = $request->format;

            $data = $this->getExportData($type, $request->date_from, $request->date_to, $anonymize);

            // Log export activity
            Log::info('Admin health data export', [
                'admin_id' => auth()->id(),
                'type' => $type,
                'format' => $format,
                'anonymized' => $anonymize,
                'record_count' => count($data)
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'type' => $type,
                    'format' => $format,
                    'anonymized' => $anonymize,
                    'exported_at' => now()->toISOString(),
                    'record_count' => count($data)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Admin health data export error', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
                'type' => $request->type
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export health data'
            ], 500);
        }
    }

    /**
     * Get export data based on type
     */
    private function getExportData(string $type, ?string $dateFrom, ?string $dateTo, bool $anonymize): array
    {
        $query = null;

        switch ($type) {
            case 'period_cycles':
                $query = PeriodCycle::query();
                if (!$anonymize) {
                    $query->with('user:id,name,email');
                }
                break;

            case 'pregnancy_records':
                $query = PregnancyRecord::query();
                if (!$anonymize) {
                    $query->with('user:id,name,email');
                }
                break;

            case 'symptoms':
                $query = PeriodSymptom::query();
                if (!$anonymize) {
                    $query->with('user:id,name,email');
                }
                break;
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        $data = $query->get()->toArray();

        // Anonymize data if requested
        if ($anonymize) {
            $data = array_map(function($record) {
                unset($record['user_id']);
                if (isset($record['user'])) {
                    unset($record['user']);
                }
                return $record;
            }, $data);
        }

        return $data;
    }
}
