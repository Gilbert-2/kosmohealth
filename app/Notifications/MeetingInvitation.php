<?php

namespace App\Notifications;

use DateTime;
use App\Models\Contact;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Spatie\CalendarLinks\Link;

class MeetingInvitation extends Notification
{
    use Queueable;

    protected $meeting;
    protected $contact;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(
        Meeting $meeting,
        Contact $contact
    ) {
        $this->meeting = $meeting;
        $this->contact = $contact;
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
        $start_date_time = $this->meeting->start_date_time->timezone('UTC')->format('Y-m-d H:i');

        $end_date_time = $this->meeting->start_date_time->addMinutes($this->meeting->period)->timezone('UTC')->format('Y-m-d H:i');

        $from = DateTime::createFromFormat('Y-m-d H:i', $start_date_time);
        $to = DateTime::createFromFormat('Y-m-d H:i', $end_date_time);

        $url = url('/m/' . $this->meeting->getMeta('identifier'));

        $description = new HtmlString("Click to Join Meeting" . "<br>\n" . $url . "<br>\n") . $this->meeting->agenda;

        $link = Link::create($this->meeting->title, $from, $to)
            ->description($description);

        return (new MailMessage)
                    ->subject('Meeting Invitation | '.config('app.name'))
                    ->greeting('Hello' . $this->contact->contact_name ? (' '.$this->contact->contact_name) : '')
                    ->line('You have been invited to a meeting!')
                    ->line($this->meeting->title)
                    ->line('Meeting Starts on ' . Carbon::parse($this->meeting->start_date_time)->timezone($notifiable->timezone)->toDateTimeString())
                    ->line('Click on the below link to know more.')
                    ->action('Meeting Details', $url)
                    ->line(new HtmlString('<a href="'.$link->google().'" target="_blank">Add to Google Calendar</a>'))
                    ->line(new HtmlString('<a href="'.$link->ics().'" target="_blank">Add to Apple Calendar</a>'))
                    ->line('If you don\'t want to join, just ignore this invitation.')
                    ->line('Thank you!');
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
