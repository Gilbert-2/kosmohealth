<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Advanced Rate Limiting Middleware
 * 
 * Provides sophisticated rate limiting with different strategies for
 * different types of requests and user behaviors.
 */
class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $limiterName
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $limiterName = 'api', int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $user = $request->user();
        $ip = $request->ip();
        
        // Create unique key for this user/IP combination
        $key = $this->resolveRequestSignature($request, $user, $limiterName);
        
        // Check current request count
        $currentAttempts = Cache::get($key, 0);
        
        if ($currentAttempts >= $maxAttempts) {
            // Log rate limit exceeded
            $this->logRateLimitExceeded($user, $ip, $limiterName, $currentAttempts);
            
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $decayMinutes * 60,
                'limit' => $maxAttempts,
                'remaining' => 0
            ], 429);
        }
        
        // Increment counter
        $newCount = $currentAttempts + 1;
        Cache::put($key, $newCount, now()->addMinutes($decayMinutes));
        
        // Add rate limit headers to response
        $response = $next($request);
        
        return $this->addRateLimitHeaders($response, $maxAttempts, $newCount, $decayMinutes);
    }

    /**
     * Resolve the rate limiting key for the request
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User|null  $user
     * @param  string  $limiterName
     * @return string
     */
    private function resolveRequestSignature(Request $request, $user, string $limiterName): string
    {
        $parts = [
            $limiterName,
            $user ? "user:{$user->id}" : "ip:" . hash('sha256', $request->ip()),
            $request->route() ? $request->route()->getName() : $request->path()
        ];
        
        return 'rate_limit:' . implode('|', $parts);
    }

    /**
     * Add rate limit headers to the response
     * 
     * @param  mixed  $response
     * @param  int  $maxAttempts
     * @param  int  $currentAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    private function addRateLimitHeaders($response, int $maxAttempts, int $currentAttempts, int $decayMinutes)
    {
        $remaining = max(0, $maxAttempts - $currentAttempts);
        $retryAfter = $remaining > 0 ? null : $decayMinutes * 60;
        
        if (method_exists($response, 'headers')) {
            $response->headers->add([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => $remaining,
                'X-RateLimit-Reset' => now()->addMinutes($decayMinutes)->timestamp
            ]);
            
            if ($retryAfter) {
                $response->headers->add(['Retry-After' => $retryAfter]);
            }
        }
        
        return $response;
    }

    /**
     * Log rate limit exceeded event
     * 
     * @param  \App\Models\User|null  $user
     * @param  string  $ip
     * @param  string  $limiterName
     * @param  int  $attempts
     * @return void
     */
    private function logRateLimitExceeded($user, string $ip, string $limiterName, int $attempts): void
    {
        try {
            Log::channel('security')->warning('Rate Limit Exceeded', [
                'user_id' => $user?->id,
                'ip_hash' => hash('sha256', $ip . config('app.key')),
                'limiter' => $limiterName,
                'attempts' => $attempts,
                'timestamp' => now()->toISOString(),
                'action' => 'rate_limit_exceeded'
            ]);
            
            // Check for potential abuse patterns
            $this->checkForAbuse($user, $ip, $limiterName);
            
        } catch (\Exception $e) {
            Log::error('Failed to log rate limit exceeded', [
                'error' => $e->getMessage(),
                'limiter' => $limiterName
            ]);
        }
    }

    /**
     * Check for potential abuse patterns
     * 
     * @param  \App\Models\User|null  $user
     * @param  string  $ip
     * @param  string  $limiterName
     * @return void
     */
    private function checkForAbuse($user, string $ip, string $limiterName): void
    {
        $abuseKey = 'abuse_check:' . ($user ? "user:{$user->id}" : "ip:" . hash('sha256', $ip));
        $abuseCount = Cache::get($abuseKey, 0) + 1;
        
        Cache::put($abuseKey, $abuseCount, now()->addHours(24));
        
        // Flag for review if multiple rate limit violations
        if ($abuseCount >= 5) {
            Log::channel('security')->alert('Potential Abuse Detected', [
                'user_id' => $user?->id,
                'ip_hash' => hash('sha256', $ip . config('app.key')),
                'abuse_count_24h' => $abuseCount,
                'limiter' => $limiterName,
                'timestamp' => now()->toISOString(),
                'action_required' => 'manual_review'
            ]);
            
            // Set suspicious activity flag
            if ($user) {
                Cache::put("suspicious_flags_{$user->id}", true, now()->addHours(24));
            }
        }
    }
}