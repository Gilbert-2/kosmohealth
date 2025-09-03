<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\ContactInfo;
use App\Services\ContactEmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    protected ContactEmailService $emailService;

    public function __construct(ContactEmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Submit contact form
     * POST /api/contact
     */
    public function submit(Request $request): JsonResponse
    {
        // Rate limiting - 5 submissions per hour per IP
        $key = 'contact-form:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('Contact form rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Too many submissions. Please try again later.',
                'retry_after' => $seconds
            ], 429);
        }

        // Validate input
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|max:255',
                'message' => 'required|string|max:5000|min:10',
                'subject' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20'
            ]);
        } catch (ValidationException $e) {
            Log::info('Contact form validation failed', [
                'errors' => $e->errors(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // Create contact message record
            $message = ContactMessage::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'message' => $validated['message'],
                'subject' => $validated['subject'] ?? 'Contact Form Submission',
                'phone' => $validated['phone'],
                'status' => 'new',
                'priority' => 'medium',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'source' => 'contact_form',
                    'referrer' => $request->header('referer'),
                    'submitted_at' => now()->toISOString()
                ]
            ]);

            // Send email to support team
            $emailResult = $this->emailService->sendContactEmail($message);

            // Send auto-reply to user (optional, don't fail if this fails)
            $this->emailService->sendAutoReply($message);

            // Increment rate limiter
            RateLimiter::hit($key, 3600); // 1 hour

            Log::info('Contact form submitted successfully', [
                'message_id' => $message->uuid,
                'email' => $message->email,
                'name' => $message->name,
                'email_sent' => $emailResult['success']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message. We will get back to you soon!',
                'message_id' => $message->uuid,
                'email_sent' => $emailResult['success']
            ], 201);

        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? null,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit your message. Please try again later.'
            ], 500);
        }
    }

    /**
     * Get contact information for frontend
     * GET /api/contact/info
     */
    public function getContactInfo(): JsonResponse
    {
        try {
            $contactInfo = ContactInfo::getCurrent();

            if (!$contactInfo) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'email' => 'info@kosmotive.rw',
                        'phone' => null,
                        'address' => null,
                        'businessHours' => 'Monday - Friday: 9:00 AM - 5:00 PM',
                        'socialLinks' => null
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $contactInfo->toFrontendArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Get contact info failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load contact information'
            ], 500);
        }
    }

    /**
     * Get contact form configuration
     * GET /api/contact/config
     */
    public function getConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'max_message_length' => 5000,
                'required_fields' => ['name', 'email', 'message'],
                'optional_fields' => ['subject', 'phone'],
                'rate_limit' => [
                    'max_attempts' => 5,
                    'window_minutes' => 60
                ],
                'auto_reply_enabled' => true,
                'expected_response_time' => '24-48 hours'
            ]
        ]);
    }
}
