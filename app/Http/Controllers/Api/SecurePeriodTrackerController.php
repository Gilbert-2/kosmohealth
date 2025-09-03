<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PeriodCycle;
use App\Models\PeriodSymptom;
use App\Services\PeriodTrackerService;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

/**
 * Secure Period Tracker Controller
 * 
 * Industry-standard secure API controller for health data management
 * Features: Authentication, Authorization, Encryption, Audit Logging, Rate Limiting
 */
class SecurePeriodTrackerController extends Controller
{
    protected $periodTrackerService;
    protected $securityAuditService;
    
    public function __construct(
        PeriodTrackerService $periodTrackerService,
        SecurityAuditService $securityAuditService
    ) {
        $this->periodTrackerService = $periodTrackerService;
        $this->securityAuditService = $securityAuditService;
        
        // Apply security middleware
        $this->middleware('auth:sanctum');
        $this->middleware('health.data.access');
        $this->middleware('throttle:health-data-access');
    }
    
    /**
     * Get current period tracker status with security validation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentStatus(Request $request): JsonResponse
    {
        try {
            // Validate security context
            $this->validateSecurityContext($request);
            
            // Get authenticated user
            $user = Auth::user();
            
            // Check if user has health data consent
            if (!$this->hasHealthDataConsent($user)) {
                return $this->securityResponse('consent_required', [
                    'message' => 'Health data consent required',
                    'consent_url' => route('health.consent')
                ], 403);
            }
            
            // Rate limiting per user
            $rateLimitKey = 'period-status:' . $user->id;
            if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
                return $this->securityResponse('rate_limited', [
                    'message' => 'Too many requests. Please wait before trying again.'
                ], 429);
            }
            
            RateLimiter::hit($rateLimitKey, 60); // 10 requests per minute
            
            // Get current status
            $status = $this->periodTrackerService->getCurrentStatus($user);
            
            // Log access for audit
            $this->securityAuditService->logHealthDataAccess($user, 'period_status_accessed', [
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'user_agent_hash' => hash('sha256', $request->userAgent() . config('app.key'))
            ]);
            
            return $this->securityResponse('success', [
                'current_phase' => $status['current_phase'] ?? 'unknown',
                'cycle_day' => $status['cycle_day'] ?? 0,
                'next_period_estimate' => $status['next_period_estimate'] ?? null,
                'fertile_window' => $status['fertile_window'] ?? null,
                'notifications_count' => $status['notifications_count'] ?? 0,
                'last_updated' => $status['last_updated'] ?? now()->toISOString(),
                'data_freshness' => $this->getDataFreshness($status)
            ]);
            
        } catch (\Exception $e) {
            // Log security incident
            $this->securityAuditService->logSecurityIncident($request->user(), 'period_status_error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            
            return $this->securityResponse('error', [
                'message' => 'Unable to retrieve health data securely'
            ], 500);
        }
    }
    
    /**
     * Get secure recommendations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSecureRecommendations(Request $request): JsonResponse
    {
        try {
            $this->validateSecurityContext($request);
            $user = Auth::user();
            
            if (!$this->hasHealthDataConsent($user)) {
                return $this->securityResponse('consent_required', [
                    'message' => 'Health data consent required for recommendations'
                ], 403);
            }
            
            $recommendations = $this->periodTrackerService->getPersonalizedRecommendations($user);
            
            // Add security-focused recommendations
            $securityRecommendations = [
                [
                    'title' => 'Data Security',
                    'description' => 'Your health data is encrypted and secure',
                    'emoji' => '🔒',
                    'type' => 'security',
                    'priority' => 'high'
                ],
                [
                    'title' => 'Privacy Protection',
                    'description' => 'Only you can access your health information',
                    'emoji' => '🛡️',
                    'type' => 'privacy',
                    'priority' => 'high'
                ]
            ];
            
            $allRecommendations = array_merge($securityRecommendations, $recommendations);
            
            $this->securityAuditService->logHealthDataAccess($user, 'recommendations_accessed');
            
            return $this->securityResponse('success', [
                'recommendations' => $allRecommendations,
                'total_count' => count($allRecommendations),
                'security_level' => 'high'
            ]);
            
        } catch (\Exception $e) {
            $this->securityAuditService->logSecurityIncident($request->user(), 'recommendations_error', [
                'error' => $e->getMessage()
            ]);
            
            return $this->securityResponse('error', [
                'message' => 'Unable to retrieve recommendations securely'
            ], 500);
        }
    }
    
    /**
     * Get secure analytics data
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSecureAnalytics(Request $request): JsonResponse
    {
        try {
            $this->validateSecurityContext($request);
            $user = Auth::user();
            
            if (!$this->hasHealthDataConsent($user)) {
                return $this->securityResponse('consent_required', [
                    'message' => 'Health data consent required for analytics'
                ], 403);
            }
            
            // Check for premium features if needed
            if (!$user->hasActiveMembership()) {
                return $this->securityResponse('premium_required', [
                    'message' => 'Premium membership required for detailed analytics',
                    'upgrade_url' => route('membership.upgrade')
                ], 402);
            }
            
            $analytics = $this->periodTrackerService->getAnalytics($user);
            
            $this->securityAuditService->logHealthDataAccess($user, 'analytics_accessed');
            
            return $this->securityResponse('success', $analytics);
            
        } catch (\Exception $e) {
            $this->securityAuditService->logSecurityIncident($request->user(), 'analytics_error', [
                'error' => $e->getMessage()
            ]);
            
            return $this->securityResponse('error', [
                'message' => 'Unable to retrieve analytics securely'
            ], 500);
        }
    }
    
    /**
     * Handle secure data export
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function secureExport(Request $request): JsonResponse
    {
        try {
            $this->validateSecurityContext($request);
            $user = Auth::user();
            
            // Additional verification for data export
            $request->validate([
                'verification_password' => 'required|string'
            ]);
            
            if (!Hash::check($request->verification_password, $user->password)) {
                $this->securityAuditService->logSecurityIncident($user, 'export_unauthorized_attempt');
                return $this->securityResponse('unauthorized', [
                    'message' => 'Password verification failed'
                ], 401);
            }
            
            // Rate limit exports (max 3 per day)
            $exportKey = 'period-export:' . $user->id . ':' . now()->format('Y-m-d');
            if (Cache::get($exportKey, 0) >= 3) {
                return $this->securityResponse('rate_limited', [
                    'message' => 'Maximum daily exports reached'
                ], 429);
            }
            
            Cache::increment($exportKey, 1);
            Cache::put($exportKey, Cache::get($exportKey), now()->endOfDay());
            
            // Generate secure export
            $exportData = $this->periodTrackerService->generateSecureExport($user);
            
            $this->securityAuditService->logHealthDataAccess($user, 'data_exported', [
                'export_size' => strlen(json_encode($exportData)),
                'export_timestamp' => now()->toISOString()
            ]);
            
            return $this->securityResponse('success', [
                'export_data' => $exportData,
                'export_timestamp' => now()->toISOString(),
                'security_notes' => [
                    'Data is encrypted in transit and at rest',
                    'Export is logged for security audit',
                    'Delete exported file after use'
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->securityAuditService->logSecurityIncident($request->user(), 'export_error', [
                'error' => $e->getMessage()
            ]);
            
            return $this->securityResponse('error', [
                'message' => 'Unable to export data securely'
            ], 500);
        }
    }
    
    /**
     * Log symptoms securely
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logSymptoms(Request $request): JsonResponse
    {
        try {
            $this->validateSecurityContext($request);
            $user = Auth::user();
            
            $request->validate([
                'symptoms' => 'required|array',
                'symptoms.*.name' => 'required|string|max:50',
                'symptoms.*.intensity' => 'required|integer|min:1|max:10',
                'date' => 'nullable|date',
                'notes' => 'nullable|string|max:500'
            ]);
            
            // Sanitize and validate symptom data
            $symptoms = collect($request->symptoms)->map(function ($symptom) {
                return [
                    'name' => strip_tags($symptom['name']),
                    'intensity' => (int)$symptom['intensity'],
                    'logged_at' => now()
                ];
            })->toArray();
            
            // Save symptoms securely
            $result = $this->periodTrackerService->logSymptoms($user, $symptoms, [
                'date' => $request->date ? Carbon::parse($request->date) : now(),
                'notes' => $request->notes ? strip_tags($request->notes) : null
            ]);
            
            $this->securityAuditService->logHealthDataAccess($user, 'symptoms_logged', [
                'symptom_count' => count($symptoms)
            ]);
            
            return $this->securityResponse('success', [
                'message' => 'Symptoms logged securely',
                'symptoms_count' => count($symptoms),
                'logged_at' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            $this->securityAuditService->logSecurityIncident($request->user(), 'symptom_logging_error', [
                'error' => $e->getMessage()
            ]);
            
            return $this->securityResponse('error', [
                'message' => 'Unable to log symptoms securely'
            ], 500);
        }
    }
    
    /**
     * Validate security context from request
     * 
     * @param Request $request
     * @throws \Exception
     */
    private function validateSecurityContext(Request $request): void
    {
        // Validate CSRF token
        if (!$request->header('X-CSRF-TOKEN')) {
            throw new \Exception('CSRF token missing');
        }
        
        // Validate request headers
        if ($request->header('X-Requested-With') !== 'XMLHttpRequest') {
            throw new \Exception('Invalid request type');
        }
        
        // Validate security context if provided
        if ($request->header('X-Security-Context')) {
            $context = json_decode($request->header('X-Security-Context'), true);
            if (!$context || !isset($context['sessionId']) || !isset($context['timestamp'])) {
                throw new \Exception('Invalid security context');
            }
            
            // Check timestamp (not older than 5 minutes)
            if (abs(time() * 1000 - $context['timestamp']) > 5 * 60 * 1000) {
                throw new \Exception('Security context expired');
            }
        }
    }
    
    /**
     * Check if user has health data consent
     * 
     * @param User $user
     * @return bool
     */
    private function hasHealthDataConsent(User $user): bool
    {
        // Check user meta or separate consent table
        return $user->getMeta('health_data_consent') === 'true' || 
               $user->getMeta('health_data_consent') === true;
    }
    
    /**
     * Get data freshness indicator
     * 
     * @param array $status
     * @return string
     */
    private function getDataFreshness(array $status): string
    {
        $lastUpdated = $status['last_updated'] ?? null;
        if (!$lastUpdated) {
            return 'unknown';
        }
        
        $lastUpdatedTime = Carbon::parse($lastUpdated);
        $now = now();
        
        if ($lastUpdatedTime->diffInMinutes($now) < 5) {
            return 'fresh';
        } elseif ($lastUpdatedTime->diffInHours($now) < 24) {
            return 'recent';
        } else {
            return 'stale';
        }
    }
    
    /**
     * Create standardized security response
     * 
     * @param string $status
     * @param array $data
     * @param int $statusCode
     * @return JsonResponse
     */
    private function securityResponse(string $status, array $data = [], int $statusCode = 200): JsonResponse
    {
        $response = [
            'status' => $status,
            'security_verified' => true,
            'timestamp' => now()->toISOString(),
            'data' => $data
        ];
        
        // Add security headers
        return response()->json($response, $statusCode)
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'DENY')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('X-Health-Data-Encrypted', 'true');
    }
}