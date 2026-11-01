<?php

namespace App\Console\Commands;

use App\Console\Concerns\PromptsForNewPassword;
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
 * default admin account or password anywhere in the codebase, and no
 * --password option: a password on the command line ends up in shell history.
 */
class CreateAdmin extends Command
{
    use PromptsForNewPassword;

    protected $signature = 'govoyza:create-admin {--name=} {--email=} {--role=owner : owner, operations or finance}
        {--password-stdin : Read the password and its confirmation as two lines from stdin}';

    protected $description = 'Create a staff account (Owner, Operations or Finance)';

    public function handle(AuditLogger $audit): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $role = $this->option('role');

        $v = Validator::make(compact('name', 'email', 'role'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_map(fn (RoleSlug $r) => $r->value, RoleSlug::staff()))],
        ]);

        if ($v->fails()) {
            return $this->failWith($v->errors()->all());
        }

        if (($password = $this->newPassword()) === null) {
            return self::FAILURE;
        }

        $v = Validator::make(compact('password'), ['password' => ['required', self::passwordRule()]]);

        if ($v->fails()) {
            return $this->failWith($v->errors()->all());
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

    /** Staff passwords: at least 12 characters with mixed case, a number and a symbol. */
    public static function passwordRule(): Password
    {
        return Password::min(12)->letters()->mixedCase()->numbers()->symbols();
    }

    /** @param  list<string>  $errors */
    private function failWith(array $errors): int
    {
        foreach ($errors as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }
}
