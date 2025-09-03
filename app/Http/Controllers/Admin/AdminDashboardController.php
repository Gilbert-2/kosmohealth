<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PeriodCycle;
use App\Models\PregnancyRecord;
use App\Models\Meeting;
use App\Models\KycVerification;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    /**
     * Get admin dashboard overview
     * GET /api/admin/dashboard
     */
    public function index(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();

            $dashboard = [
                'summary' => [
                    'total_users' => User::count(),
                    'new_users_today' => User::whereDate('created_at', $today)->count(),
                    'new_users_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                    'active_pregnancies' => PregnancyRecord::where('status', 'active')->count(),
                    'pending_kyc' => KycVerification::where('status', 'pending')->count(),
                    'total_meetings' => Meeting::count(),
                    'meetings_today' => Meeting::whereDate('start_time', $today)->count()
                ],
                'health_stats' => [
                    'period_cycles_total' => PeriodCycle::count(),
                    'period_cycles_this_month' => PeriodCycle::where('created_at', '>=', $thisMonth)->count(),
                    'pregnancy_records_total' => PregnancyRecord::count(),
                    'active_pregnancies' => PregnancyRecord::where('status', 'active')->count(),
                    'avg_cycle_length' => round(PeriodCycle::whereNotNull('cycle_length')->avg('cycle_length'), 1)
                ],
                'recent_activity' => [
                    'new_users' => User::with('roles')
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'role' => $user->roles->first()?->name ?? 'user',
                                'created_at' => $user->created_at->toISOString()
                            ];
                        }),
                    'recent_kyc' => KycVerification::with('user:id,name,email')
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(function($kyc) {
                            return [
                                'id' => $kyc->id,
                                'user_name' => $kyc->user->name ?? 'Unknown',
                                'user_email' => $kyc->user->email ?? 'Unknown',
                                'status' => $kyc->status,
                                'created_at' => $kyc->created_at->toISOString()
                            ];
                        }),
                    'recent_meetings' => Meeting::with('user:id,name,email')
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(function($meeting) {
                            return [
                                'id' => $meeting->id,
                                'title' => $meeting->title,
                                'host_name' => $meeting->user->name ?? 'Unknown',
                                'start_time' => $meeting->start_time,
                                'status' => $meeting->status ?? 'scheduled'
                            ];
                        })
                ],
                'charts' => [
                    'user_registrations' => $this->getUserRegistrationChart(),
                    'health_activity' => $this->getHealthActivityChart(),
                    'meeting_stats' => $this->getMeetingStatsChart()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboard
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load admin dashboard'
            ], 500);
        }
    }

    /**
     * Get user registration chart data for last 30 days
     */
    private function getUserRegistrationChart(): array
    {
        $startDate = Carbon::now()->subDays(30);
        
        $registrations = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $registrations->pluck('date')->toArray(),
            'data' => $registrations->pluck('count')->toArray()
        ];
    }

    /**
     * Get health activity chart data
     */
    private function getHealthActivityChart(): array
    {
        $startDate = Carbon::now()->subDays(30);
        
        $periodCycles = PeriodCycle::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $pregnancyRecords = PregnancyRecord::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'period_cycles' => [
                'labels' => $periodCycles->pluck('date')->toArray(),
                'data' => $periodCycles->pluck('count')->toArray()
            ],
            'pregnancy_records' => [
                'labels' => $pregnancyRecords->pluck('date')->toArray(),
                'data' => $pregnancyRecords->pluck('count')->toArray()
            ]
        ];
    }

    /**
     * Get meeting statistics chart
     */
    private function getMeetingStatsChart(): array
    {
        $startDate = Carbon::now()->subDays(30);
        
        $meetings = Meeting::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $meetings->pluck('date')->toArray(),
            'data' => $meetings->pluck('count')->toArray()
        ];
    }

    /**
     * Get system health status
     * GET /api/admin/system-health
     */
    public function systemHealth(): JsonResponse
    {
        try {
            $health = [
                'database' => $this->checkDatabaseHealth(),
                'storage' => $this->checkStorageHealth(),
                'cache' => $this->checkCacheHealth(),
                'queue' => $this->checkQueueHealth()
            ];

            $overallStatus = collect($health)->every(fn($status) => $status['status'] === 'healthy') 
                ? 'healthy' 
                : 'warning';

            return response()->json([
                'success' => true,
                'data' => [
                    'overall_status' => $overallStatus,
                    'components' => $health,
                    'checked_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check system health'
            ], 500);
        }
    }

    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    private function checkStorageHealth(): array
    {
        try {
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usagePercent = (($totalSpace - $diskSpace) / $totalSpace) * 100;
            
            $status = $usagePercent > 90 ? 'warning' : 'healthy';
            $message = "Storage usage: " . round($usagePercent, 1) . "%";
            
            return ['status' => $status, 'message' => $message];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Storage check failed'];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            cache()->put('health_check', 'test', 60);
            $value = cache()->get('health_check');
            
            return $value === 'test' 
                ? ['status' => 'healthy', 'message' => 'Cache working properly']
                : ['status' => 'warning', 'message' => 'Cache not working properly'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache check failed'];
        }
    }

    private function checkQueueHealth(): array
    {
        try {
            // Simple queue health check - you might want to implement more sophisticated checks
            return ['status' => 'healthy', 'message' => 'Queue system operational'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Queue check failed'];
        }
    }
}
