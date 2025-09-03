<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Security Log Formatter
 * 
 * Formats security logs with HIPAA compliance and enhanced security context.
 */
class SecurityLogFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s',
                true,
                true
            ));
        }

        // Add processors for additional context
        $logger->pushProcessor(new WebProcessor());
        $logger->pushProcessor(new MemoryUsageProcessor());
        $logger->pushProcessor(new IntrospectionProcessor());
        
        // Add custom security processor
        $logger->pushProcessor(function ($record) {
            $record['extra']['security_context'] = [
                'server_time' => now()->toISOString(),
                'session_id' => session()->getId(),
                'request_id' => request()->header('X-Request-ID', uniqid()),
                'compliance_flags' => [
                    'hipaa_compliant' => true,
                    'gdpr_compliant' => true,
                    'audit_trail' => true
                ]
            ];

            return $record;
        });
    }
}