<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $title = $this->meeting->title ?: 'Meeting';
        return (new MailMessage)
            ->subject('You have a new consultation scheduled')
            ->line("A meeting has been scheduled: {$title}")
            ->line('Start Time: ' . optional($this->meeting->start_date_time)->toDateTimeString())
            ->line('Meeting ID: ' . $this->meeting->uuid)
            ->action('Join Meeting', url('/m/' . $this->meeting->uuid))
            ->line('Click the button above to join your consultation session.');
    }

    public function toArray($notifiable)
    {
        return [
            'meeting_uuid' => $this->meeting->uuid,
            'title' => $this->meeting->title,
            'start_date_time' => optional($this->meeting->start_date_time)->toDateTimeString(),
            'join_url' => url('/m/' . $this->meeting->uuid),
        ];
    }
}


