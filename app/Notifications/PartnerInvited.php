<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartnerInvited extends Notification
{
    public function __construct(public readonly string $url, public readonly CarbonInterface $expiresAt) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your invitation to become a GoVoyza partner')
            ->line('GoVoyza has invited you to join its network of visa service partners.')
            ->line('Create your partner account with the link below. It works once and expires on '
                .$this->expiresAt->format('j F Y').'.')
            ->action('Create partner account', $this->url)
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }
}
