<?php

namespace App\Console\Commands;

use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Creates a staff account (Owner by default). There is intentionally no
 * default admin account or password anywhere in the codebase.
 */
class CreateAdmin extends Command
{
    protected $signature = 'govoyza:create-admin {--name=} {--email=} {--role=owner : owner, operations or finance} {--password= : Omit to be prompted securely}';

    protected $description = 'Create a staff account (Owner, Operations or Finance)';

    public function handle(AuditLogger $audit): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $role = $this->option('role');
        $password = $this->option('password') ?: $this->secret('Password');

        $v = Validator::make(compact('name', 'email', 'role', 'password'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_map(fn (RoleSlug $r) => $r->value, RoleSlug::staff()))],
            'password' => ['required', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User(compact('name', 'email', 'password'));
        $user->assignRole(RoleSlug::from($role));
        $user->status = UserStatus::Active;
        $user->email_verified_at = now();
        $user->save();

        $audit->log('admin.created_via_cli', $user, new: ['email' => $email, 'role' => $role]);
        $this->info("{$user->role->name} {$email} created.");

        return self::SUCCESS;
    }
}
