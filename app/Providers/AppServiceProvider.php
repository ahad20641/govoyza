<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Strong password policy everywhere Password::defaults() is used.
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
            : Password::min(8)->letters()->numbers());

        // RBAC: Super Admin passes every check; everyone else needs the permission
        // on their role. Returning null lets Policies decide record-level access.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return $user->hasPermission($ability) ? true : null;
        });

        RateLimiter::for('auth', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('password-reset', fn (Request $r) => [
            Limit::perMinute(3)->by(strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perHour(20)->by($r->ip()),
        ]);
        RateLimiter::for('registration', fn (Request $r) => Limit::perHour(10)->by($r->ip()));

        ResetPassword::createUrlUsing(fn ($user, string $token) => route('password.reset', [
            'token' => $token, 'email' => $user->getEmailForPasswordReset(),
        ]));
    }
}
