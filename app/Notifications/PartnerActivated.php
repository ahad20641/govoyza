<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PartnerActivated extends Notification
{
    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your GoVoyza partner account is active')
            ->greeting("Hello {$notifiable->name},")
            ->line('We have reviewed your documents and your partner account is now active.')
            ->action('Open the partner portal', route('filament.partner.pages.dashboard'));
    }
}
