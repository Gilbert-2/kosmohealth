<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    protected $user;
    protected $code;

    public function __construct($user, $code)
    {
        $this->user = $user;
        $this->code = $code;
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
                    ->subject(__('email.password_reset_code.subject') . ' | '.config('app.name'))
                    ->greeting(__('email.password_reset_code.greeting') . ' ' . $this->user->name)
                    ->line(__('email.password_reset_code.line_1'))
                    ->line(__('email.password_reset_code.line_2') . ' ' . $this->code)
                    ->line(__('email.password_reset_code.line_3_before') . ' ' . config('config.auth.reset_password_token_lifetime') . __('email.password_reset_code.line_3_after'))
                    ->line(__('email.password_reset_code.line_4'))
                    ->line(__('email.password_reset_code.line_5'));

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
