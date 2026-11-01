<?php

namespace App\Console\Commands;

use App\Console\Concerns\PromptsForNewPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Turns the staging gate (App\Http\Middleware\StagingGate) on or changes its
 * password. Writes only a bcrypt hash to .env, then refreshes the config cache.
 */
class SetStagingPassword extends Command
{
    use PromptsForNewPassword;

    protected $signature = 'govoyza:staging-password {--user=govoyza : Username for the browser prompt}
        {--password-stdin : Read the password and its confirmation as two lines from stdin}';

    protected $description = 'Set the HTTP password that keeps the staging site private';

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Refusing: the staging gate must never be turned on in production.');

            return self::FAILURE;
        }

        $user = (string) $this->option('user');

        if (! preg_match('/^[A-Za-z0-9._-]{3,40}$/', $user)) {
            $this->error('The username must be 3-40 letters, digits, dots, dashes or underscores.');

            return self::FAILURE;
        }

        if (($password = $this->newPassword()) === null) {
            return self::FAILURE;
        }

        $v = Validator::make(compact('password'), ['password' => ['required', Password::min(12)->letters()->numbers()]]);

        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->writeEnv([
            'GOVOYZA_STAGING_USER' => $user,
            'GOVOYZA_STAGING_PASSWORD_HASH' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        if ($this->laravel->configurationIsCached()) {
            $this->call('config:cache');
        }

        $this->info("Staging gate on. Username: {$user}");

        return self::SUCCESS;
    }

    /**
     * Sets or replaces each key in .env. Values are single-quoted so the `$` in a
     * bcrypt hash is not treated as a variable.
     *
     * @param  array<string, string>  $values
     */
    private function writeEnv(array $values): void
    {
        $path = $this->laravel->environmentFilePath();
        $env = is_file($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $line = "{$key}='{$value}'";
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            $env = preg_match($pattern, $env)
                ? preg_replace_callback($pattern, fn () => $line, $env)
                : rtrim($env, "\n")."\n{$line}\n";
        }

        file_put_contents($path, $env, LOCK_EX);
    }
}
