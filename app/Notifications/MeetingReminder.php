<?php

namespace App\Notifications;

use App\Helpers\CalHelper;
use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingReminder extends Notification
{
    use Queueable;

    public $meeting;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject(trans('meeting.reminder.notification_subject', ['title' => $this->meeting->title]))
                    ->line(trans('meeting.reminder.notification_title'))
                    ->line(trans('meeting.reminder.notification_line1', ['title' => $this->meeting->title]))
                    ->line(trans('meeting.reminder.notification_line2', ['date' => CalHelper::showDateTime($this->meeting->start_date_time)]))
                    ->action(trans('meeting.reminder.notification_cta'), url('/m/' . $this->meeting->getMeta('identifier')))
                    ->line(trans('meeting.reminder.notification_footer'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
