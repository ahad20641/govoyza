<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent straight away, not queued: the database queue only runs once a minute
 * from the scheduler, which is too slow for a code the user is waiting for.
 */
class EmailVerificationCode extends Notification
{
    public function __construct(public readonly string $code, public readonly int $minutes) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your GoVoyza verification code: {$this->code}")
            ->greeting("Hello {$notifiable->name},")
            ->line('Enter this code to verify your email address:')
            ->line("**{$this->code}**")
            ->line("The code expires in {$this->minutes} minutes and works once.")
            ->line('If you did not create a GoVoyza account, you can ignore this email.');
    }
}
