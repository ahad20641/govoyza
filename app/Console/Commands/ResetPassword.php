<?php

namespace App\Console\Commands;

use App\Console\Concerns\PromptsForNewPassword;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Sets a new password for any account from the server shell, e.g. when a
 * password was exposed. Signs the account out everywhere.
 */
class ResetPassword extends Command
{
    use PromptsForNewPassword;

    protected $signature = 'govoyza:reset-password {email}
        {--password-stdin : Read the password and its confirmation as two lines from stdin}';

    protected $description = 'Set a new password for an account and sign it out everywhere';

    public function handle(AuditLogger $audit): int
    {
        $user = User::firstWhere('email', strtolower($this->argument('email')));

        if (! $user) {
            $this->error('No account has that email address.');

            return self::FAILURE;
        }

        if (($password = $this->newPassword()) === null) {
            return self::FAILURE;
        }

        $rule = $user->isStaff() ? CreateAdmin::passwordRule() : Password::defaults();
        $v = Validator::make(compact('password'), ['password' => ['required', $rule]]);

        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $password) {
            $user->password = $password;
            $user->setRememberToken(Str::random(60));
            $user->save();

            // Database sessions: drop every open session of this user.
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        });

        $audit->log('auth.password_reset_via_cli', $user, userId: $user->id);
        $this->info("Password changed for {$user->email}. All of its sessions were signed out.");

        return self::SUCCESS;
    }
}
