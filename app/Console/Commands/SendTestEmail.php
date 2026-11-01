<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Checks the MAIL_* settings on a server by sending one plain email. */
class SendTestEmail extends Command
{
    protected $signature = 'govoyza:send-test-email {to}';

    protected $description = 'Send a test email using the current mail settings';

    public function handle(): int
    {
        $to = $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'This is a test email from GoVoyza ('.config('app.env').'). If you can read it, outgoing mail works.',
                fn ($message) => $message->to($to)->subject('GoVoyza test email'),
            );
        } catch (Throwable $e) {
            // The message names the failing step (connect, TLS, auth) without the password.
            $this->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent to '.$to.' via '.config('mail.default').' ('.config('mail.mailers.smtp.host').').');

        return self::SUCCESS;
    }
}
