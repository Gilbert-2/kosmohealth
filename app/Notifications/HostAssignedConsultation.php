<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class HostAssignedConsultation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $bookingId, public int $userId)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        $booking = \App\Models\ConsultationBooking::find($this->bookingId);
        return [
            'type' => 'consultation_assigned',
            'booking_id' => $this->bookingId,
            'user_id' => $this->userId,
            'preferred_datetime' => optional($booking)->preferred_datetime_iso,
            'reason_key' => optional($booking)->reason_key,
            'notes' => optional($booking)->notes,
        ];
    }

    public function toMail($notifiable)
    {
        $booking = \App\Models\ConsultationBooking::find($this->bookingId);
        $user = \App\Models\User::find($this->userId);
        $when = optional($booking && $booking->preferred_datetime ? \Carbon\Carbon::parse($booking->preferred_datetime) : null);
        $whenText = $when ? $when->toDayDateTimeString() : 'TBD';
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('New consultation assignment')
            ->line('You have been assigned a new consultation.')
            ->line('Patient: ' . ($user?->name ?: ('User #' . $this->userId)))
            ->line('Preferred time: ' . $whenText)
            ->line('Reason: ' . ($booking?->reason_key ?? 'n/a'))
            ->line('Notes: ' . ($booking?->notes ?? ''));
    }
}
