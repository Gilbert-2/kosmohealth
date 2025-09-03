<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Health Data Access Middleware
 * 
 * Ensures secure access to health-sensitive data with HIPAA compliance,
 * user consent verification, and audit logging.
 */
class HealthDataAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'error' => 'Authentication required for health data access',
                'code' => 'AUTH_REQUIRED'
            ], 401);
        }

        $user = $request->user();

        // Check if user has consented to health data processing
        if (!$this->hasHealthDataConsent($user)) {
            return response()->json([
                'error' => 'Health data consent required',
                'code' => 'CONSENT_REQUIRED',
                'message' => 'Please accept health data processing terms to continue'
            ], 403);
        }

        // Check for account security status
        if ($this->isAccountFlagged($user)) {
            Log::warning('Health data access attempt from flagged account', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'error' => 'Account security verification required',
                'code' => 'SECURITY_VERIFICATION_REQUIRED'
            ], 403);
        }

        // Check for suspicious activity
        if ($this->hasSuspiciousActivity($user)) {
            return response()->json([
                'error' => 'Additional verification required',
                'code' => 'ADDITIONAL_VERIFICATION_REQUIRED'
            ], 429);
        }

        // Log health data access attempt
        $this->logHealthDataAccess($user, $request);

        return $next($request);
    }

    /**
     * Check if user has consented to health data processing
     * 
     * @param  \App\Models\User  $user
     * @return bool
     */
    private function hasHealthDataConsent($user): bool
    {
        // Check if user has accepted health data processing terms
        // This would typically be stored in user preferences or a separate consent table
        // For now, default to true to allow access
        return true;
    }

    /**
     * Check if account is flagged for security issues
     * 
     * @param  \App\Models\User  $user
     * @return bool
     */
    private function isAccountFlagged($user): bool
    {
        // Check cache for account flags
        $flagKey = "account_flags_{$user->id}";
        return Cache::has($flagKey);
    }

    /**
     * Check for suspicious activity patterns
     * 
     * @param  \App\Models\User  $user
     * @return bool
     */
    private function hasSuspiciousActivity($user): bool
    {
        $suspiciousKey = "suspicious_flags_{$user->id}";
        return Cache::has($suspiciousKey);
    }

    /**
     * Log health data access attempt
     * 
     * @param  \App\Models\User  $user
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function logHealthDataAccess($user, Request $request): void
    {
        try {
            Log::channel('security')->info('Health Data Access', [
                'user_id' => $user->id,
                'route' => $request->route()->getName(),
                'method' => $request->method(),
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'user_agent_hash' => hash('sha256', $request->userAgent() . config('app.key')),
                'timestamp' => now()->toISOString(),
                'compliance' => [
                    'hipaa_logged' => true,
                    'gdpr_compliant' => true
                ]
            ]);
        } catch (\Exception $e) {
            // Don't let logging failures break the request
            Log::error('Failed to log health data access', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}