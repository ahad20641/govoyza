<?php

namespace App\Console\Commands;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first Super Admin. There is intentionally no default admin
 * account or password anywhere in the codebase.
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'govoyza:create-admin {--name=} {--email=} {--password= : Omit to be prompted securely}';

    protected $description = 'Create a Super Admin account';

    public function handle(AuditLogger $audit): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password');

        $v = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User(compact('name', 'email', 'password'));
        $user->role_id = Role::forSlug(RoleSlug::SuperAdmin)->id;
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();
        $user->save();

        $audit->log('admin.created_via_cli', $user, new: ['email' => $email]);
        $this->info("Super Admin {$email} created.");

        return self::SUCCESS;
    }
}
