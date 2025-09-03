<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Health Controller
 * 
 * Basic health endpoint for API monitoring and status checks.
 */
class HealthController extends Controller
{
    /**
     * API Health Check
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0')
        ]);
    }

    /**
     * Detailed Health Status
     * 
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $status = [
            'api' => 'healthy',
            'database' => 'unknown',
            'cache' => 'unknown',
            'timestamp' => now()->toISOString()
        ];

        try {
            // Test database connection
            \DB::connection()->getPdo();
            $status['database'] = 'healthy';
        } catch (\Exception $e) {
            $status['database'] = 'unhealthy';
        }

        try {
            // Test cache connection
            \Cache::put('health_check', 'test', 1);
            \Cache::forget('health_check');
            $status['cache'] = 'healthy';
        } catch (\Exception $e) {
            $status['cache'] = 'unhealthy';
        }

        $httpStatus = ($status['database'] === 'healthy' && $status['cache'] === 'healthy') ? 200 : 503;

        return response()->json([
            'status' => $httpStatus === 200 ? 'healthy' : 'degraded',
            'components' => $status
        ], $httpStatus);
    }
}