<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\ContactInfo;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class ContactEmailService
{
    /**
     * Send contact form email to support team
     */
    public function sendContactEmail(ContactMessage $message): array
    {
        try {
            $contactInfo = ContactInfo::getCurrent();
            $supportEmail = $contactInfo?->support_email ?? 'comms@kosmotive.rw';

            // Prepare email data
            $emailData = [
                'name' => $message->name,
                'email' => $message->email,
                'subject' => $message->subject ?? 'New Contact Form Submission',
                'message' => $message->message,
                'phone' => $message->phone,
                'submitted_at' => $message->created_at->format('Y-m-d H:i:s'),
                'message_id' => $message->uuid
            ];

            // Send email using Laravel's Mail facade
            Mail::send('emails.contact-form', $emailData, function ($mail) use ($supportEmail, $emailData, $message) {
                $mail->to($supportEmail)
                     ->subject($emailData['subject'])
                     ->replyTo($message->email, $message->name);
            });

            // Update message with email sent timestamp
            $message->update([
                'email_sent_at' => now(),
                'email_response' => [
                    'status' => 'sent',
                    'sent_to' => $supportEmail,
                    'sent_at' => now()->toISOString()
                ]
            ]);

            Log::info('Contact form email sent successfully', [
                'message_id' => $message->uuid,
                'support_email' => $supportEmail,
                'sender_email' => $message->email
            ]);

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'sent_to' => $supportEmail
            ];

        } catch (Exception $e) {
            Log::error('Contact form email failed', [
                'message_id' => $message->uuid,
                'error' => $e->getMessage(),
                'sender_email' => $message->email
            ]);

            // Update message with error information
            $message->update([
                'email_response' => [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toISOString()
                ]
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send auto-reply email to the contact form submitter
     */
    public function sendAutoReply(ContactMessage $message): array
    {
        try {
            $contactInfo = ContactInfo::getCurrent();

            // Prepare auto-reply data
            $emailData = [
                'name' => $message->name,
                'message_id' => $message->uuid,
                'contact_info' => $contactInfo?->toFrontendArray(),
                'submitted_at' => $message->created_at->format('Y-m-d H:i:s')
            ];

            // Send auto-reply email
            Mail::send('emails.contact-auto-reply', $emailData, function ($mail) use ($message, $contactInfo) {
                $fromEmail = $contactInfo?->email ?? 'noreply@kosmotive.rw';
                $mail->to($message->email, $message->name)
                     ->from($fromEmail, 'KosmoHealth Support')
                     ->subject('Thank you for contacting KosmoHealth');
            });

            Log::info('Contact form auto-reply sent', [
                'message_id' => $message->uuid,
                'recipient_email' => $message->email
            ]);

            return [
                'success' => true,
                'message' => 'Auto-reply sent successfully'
            ];

        } catch (Exception $e) {
            Log::error('Contact form auto-reply failed', [
                'message_id' => $message->uuid,
                'error' => $e->getMessage(),
                'recipient_email' => $message->email
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send auto-reply',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retry sending failed emails
     */
    public function retryFailedEmail(ContactMessage $message): array
    {
        if ($message->email_sent_at) {
            return [
                'success' => false,
                'message' => 'Email already sent successfully'
            ];
        }

        return $this->sendContactEmail($message);
    }
}
