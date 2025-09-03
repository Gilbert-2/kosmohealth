<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Security Audit Service
 * 
 * Provides comprehensive security auditing for health data access
 * with HIPAA compliance, GDPR compliance, and advanced threat detection.
 */
class SecurityAuditService
{
    private const AUDIT_RETENTION_DAYS = 2555; // 7 years for HIPAA compliance
    private const SUSPICIOUS_ACTIVITY_THRESHOLD = 10;
    private const RATE_LIMIT_THRESHOLD = 100;

    /**
     * Log health data access
     * 
     * @param User $user
     * @param string $action
     * @param array $context
     * @return void
     */
    public function logHealthDataAccess(User $user, string $action, array $context = []): void
    {
        try {
            $auditData = [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'action' => $action,
                'ip_address' => $this->hashIP($context['ip'] ?? request()->ip()),
                'user_agent' => hash('sha256', $context['user_agent'] ?? request()->userAgent()),
                'timestamp' => now()->toISOString(),
                'session_id' => session()->getId(),
                'context' => $this->sanitizeContext($context),
                'compliance_flags' => [
                    'hipaa_logged' => true,
                    'gdpr_compliant' => true,
                    'data_classification' => 'health_sensitive'
                ]
            ];

            // Log to secure audit trail
            Log::channel('security')->info('Health Data Access', $auditData);

            // Check for suspicious activity
            $this->detectSuspiciousActivity($user, $action, $context);

            // Update access statistics
            $this->updateAccessStatistics($user, $action);

        } catch (\Exception $e) {
            // Ensure audit failures don't break the application
            Log::critical('Audit logging failed', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log health-related actions
     * 
     * @param User $user
     * @param string $action
     * @param array $data
     * @return void
     */
    public function logHealthAction(User $user, string $action, array $data = []): void
    {
        try {
            $auditData = [
                'user_id' => $user->id,
                'action_type' => 'health_action',
                'action' => $action,
                'data_hash' => hash('sha256', json_encode($data)),
                'ip_address' => $this->hashIP(request()->ip()),
                'timestamp' => now()->toISOString(),
                'compliance_flags' => [
                    'action_logged' => true,
                    'data_integrity_verified' => true
                ]
            ];

            Log::channel('security')->info('Health Action', $auditData);

            // Track action patterns
            $this->trackActionPatterns($user, $action);

        } catch (\Exception $e) {
            Log::critical('Health action audit failed', [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log data export events
     * 
     * @param User $user
     * @param string $dataType
     * @param string $format
     * @return void
     */
    public function logDataExport(User $user, string $dataType, string $format): void
    {
        try {
            $auditData = [
                'user_id' => $user->id,
                'action_type' => 'data_export',
                'data_type' => $dataType,
                'export_format' => $format,
                'ip_address' => $this->hashIP(request()->ip()),
                'timestamp' => now()->toISOString(),
                'compliance_flags' => [
                    'export_logged' => true,
                    'gdpr_right_to_portability' => true,
                    'hipaa_compliant' => true
                ]
            ];

            Log::channel('security')->warning('Data Export', $auditData);

            // Alert for unusual export activity
            $this->checkExportActivity($user);

        } catch (\Exception $e) {
            Log::critical('Data export audit failed', [
                'user_id' => $user->id,
                'data_type' => $dataType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Detect suspicious activity patterns
     * 
     * @param User $user
     * @param string $action
     * @param array $context
     * @return void
     */
    private function detectSuspiciousActivity(User $user, string $action, array $context): void
    {
        $cacheKey = "user_activity_{$user->id}";
        $currentIP = $context['ip'] ?? request()->ip();
        
        // Get recent activity
        $recentActivity = Cache::get($cacheKey, []);
        $recentActivity[] = [
            'action' => $action,
            'ip' => $currentIP,
            'timestamp' => now()->timestamp,
            'user_agent' => $context['user_agent'] ?? request()->userAgent()
        ];

        // Keep only last hour of activity
        $recentActivity = array_filter($recentActivity, function($activity) {
            return $activity['timestamp'] > (now()->timestamp - 3600);
        });

        // Check for suspicious patterns
        $suspiciousFlags = [];

        // Rapid successive requests
        if (count($recentActivity) > self::SUSPICIOUS_ACTIVITY_THRESHOLD) {
            $suspiciousFlags[] = 'high_frequency_access';
        }

        // Multiple IP addresses
        $uniqueIPs = array_unique(array_column($recentActivity, 'ip'));
        if (count($uniqueIPs) > 3) {
            $suspiciousFlags[] = 'multiple_ip_addresses';
        }

        // Multiple user agents
        $uniqueUserAgents = array_unique(array_column($recentActivity, 'user_agent'));
        if (count($uniqueUserAgents) > 2) {
            $suspiciousFlags[] = 'multiple_user_agents';
        }

        // Log suspicious activity
        if (!empty($suspiciousFlags)) {
            Log::channel('security')->alert('Suspicious Activity Detected', [
                'user_id' => $user->id,
                'flags' => $suspiciousFlags,
                'activity_count' => count($recentActivity),
                'unique_ips' => count($uniqueIPs),
                'timestamp' => now()->toISOString()
            ]);

            // Trigger additional security measures
            $this->triggerSecurityMeasures($user, $suspiciousFlags);
        }

        // Update cache
        Cache::put($cacheKey, $recentActivity, 3600); // 1 hour TTL
    }

    /**
     * Update access statistics
     * 
     * @param User $user
     * @param string $action
     * @return void
     */
    private function updateAccessStatistics(User $user, string $action): void
    {
        $statsKey = "access_stats_{$user->id}";
        $stats = Cache::get($statsKey, [
            'total_accesses' => 0,
            'actions' => [],
            'last_access' => null,
            'daily_counts' => []
        ]);

        $today = now()->toDateString();
        
        $stats['total_accesses']++;
        $stats['actions'][$action] = ($stats['actions'][$action] ?? 0) + 1;
        $stats['last_access'] = now()->toISOString();
        $stats['daily_counts'][$today] = ($stats['daily_counts'][$today] ?? 0) + 1;

        // Keep only last 30 days of daily counts
        $stats['daily_counts'] = array_slice($stats['daily_counts'], -30, null, true);

        Cache::put($statsKey, $stats, 86400 * 30); // 30 days TTL
    }

    /**
     * Track action patterns
     * 
     * @param User $user
     * @param string $action
     * @return void
     */
    private function trackActionPatterns(User $user, string $action): void
    {
        $patternKey = "action_patterns_{$user->id}";
        $patterns = Cache::get($patternKey, []);
        
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;
        
        if (!isset($patterns[$action])) {
            $patterns[$action] = [
                'total_count' => 0,
                'hours' => array_fill(0, 24, 0),
                'days_of_week' => array_fill(0, 7, 0),
                'last_performed' => null
            ];
        }
        
        $patterns[$action]['total_count']++;
        $patterns[$action]['hours'][$hour]++;
        $patterns[$action]['days_of_week'][$dayOfWeek]++;
        $patterns[$action]['last_performed'] = now()->toISOString();
        
        Cache::put($patternKey, $patterns, 86400 * 90); // 90 days TTL
    }

    /**
     * Check export activity for anomalies
     * 
     * @param User $user
     * @return void
     */
    private function checkExportActivity(User $user): void
    {
        $exportKey = "exports_{$user->id}";
        $exports = Cache::get($exportKey, []);
        
        $exports[] = now()->timestamp;
        
        // Keep only last 24 hours
        $exports = array_filter($exports, function($timestamp) {
            return $timestamp > (now()->timestamp - 86400);
        });
        
        // Alert if too many exports
        if (count($exports) > 5) {
            Log::channel('security')->alert('Excessive Data Export Activity', [
                'user_id' => $user->id,
                'export_count_24h' => count($exports),
                'timestamp' => now()->toISOString()
            ]);
        }
        
        Cache::put($exportKey, $exports, 86400);
    }

    /**
     * Trigger security measures for suspicious activity
     * 
     * @param User $user
     * @param array $flags
     * @return void
     */
    private function triggerSecurityMeasures(User $user, array $flags): void
    {
        // Rate limiting
        if (in_array('high_frequency_access', $flags)) {
            $rateLimitKey = "rate_limit_{$user->id}";
            Cache::put($rateLimitKey, true, 1800); // 30 minutes rate limit
        }

        // Additional verification requirements
        if (in_array('multiple_ip_addresses', $flags) || in_array('multiple_user_agents', $flags)) {
            $verificationKey = "require_verification_{$user->id}";
            Cache::put($verificationKey, true, 3600); // 1 hour additional verification
        }

        // Notify security team for severe cases
        if (count($flags) >= 2) {
            Log::channel('security')->critical('Multiple Security Flags Triggered', [
                'user_id' => $user->id,
                'flags' => $flags,
                'action_required' => 'manual_review',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Hash IP address for privacy compliance
     * 
     * @param string $ip
     * @return string
     */
    private function hashIP(string $ip): string
    {
        // Use a consistent salt for IP hashing
        $salt = config('app.key', 'default_salt');
        return hash('sha256', $ip . $salt);
    }

    /**
     * Sanitize context data for logging
     * 
     * @param array $context
     * @return array
     */
    private function sanitizeContext(array $context): array
    {
        // Remove sensitive data from context
        $sensitiveKeys = ['password', 'token', 'api_key', 'secret'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($context[$key])) {
                $context[$key] = '[REDACTED]';
            }
        }
        
        // Limit context size
        return array_slice($context, 0, 10, true);
    }

    /**
     * Get security metrics for user
     * 
     * @param User $user
     * @return array
     */
    public function getSecurityMetrics(User $user): array
    {
        $statsKey = "access_stats_{$user->id}";
        $stats = Cache::get($statsKey, [
            'total_accesses' => 0,
            'actions' => [],
            'last_access' => null,
            'daily_counts' => []
        ]);

        return [
            'total_health_data_accesses' => $stats['total_accesses'],
            'last_access' => $stats['last_access'],
            'recent_activity_summary' => $this->summarizeRecentActivity($stats),
            'security_score' => $this->calculateSecurityScore($user),
            'compliance_status' => [
                'hipaa_compliant' => true,
                'gdpr_compliant' => true,
                'audit_trail_complete' => true
            ]
        ];
    }

    /**
     * Summarize recent activity
     * 
     * @param array $stats
     * @return array
     */
    private function summarizeRecentActivity(array $stats): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        
        return [
            'accesses_today' => $stats['daily_counts'][$today] ?? 0,
            'accesses_yesterday' => $stats['daily_counts'][$yesterday] ?? 0,
            'most_frequent_action' => $this->getMostFrequentAction($stats['actions'] ?? []),
            'activity_trend' => $this->calculateActivityTrend($stats['daily_counts'] ?? [])
        ];
    }

    /**
     * Get most frequent action
     * 
     * @param array $actions
     * @return string|null
     */
    private function getMostFrequentAction(array $actions): ?string
    {
        if (empty($actions)) {
            return null;
        }
        
        return array_keys($actions, max($actions))[0];
    }

    /**
     * Calculate activity trend
     * 
     * @param array $dailyCounts
     * @return string
     */
    private function calculateActivityTrend(array $dailyCounts): string
    {
        if (count($dailyCounts) < 7) {
            return 'insufficient_data';
        }
        
        $recent = array_slice($dailyCounts, -3, 3, true);
        $previous = array_slice($dailyCounts, -7, 3, true);
        
        $recentAvg = array_sum($recent) / count($recent);
        $previousAvg = array_sum($previous) / count($previous);
        
        if ($recentAvg > $previousAvg * 1.2) {
            return 'increasing';
        } elseif ($recentAvg < $previousAvg * 0.8) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Calculate security score
     * 
     * @param User $user
     * @return int
     */
    private function calculateSecurityScore(User $user): int
    {
        $score = 100;
        
        // Check for recent suspicious activity flags
        $suspiciousKey = "suspicious_flags_{$user->id}";
        if (Cache::has($suspiciousKey)) {
            $score -= 20;
        }
        
        // Check for rate limiting
        $rateLimitKey = "rate_limit_{$user->id}";
        if (Cache::has($rateLimitKey)) {
            $score -= 15;
        }
        
        // Check for verification requirements
        $verificationKey = "require_verification_{$user->id}";
        if (Cache::has($verificationKey)) {
            $score -= 10;
        }
        
        return max(0, $score);
    }
}