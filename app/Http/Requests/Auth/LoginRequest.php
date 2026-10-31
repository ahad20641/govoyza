<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /** @throws ValidationException */
    public function authenticate(AuditLogger $audit): User
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 15 * 60);
            $audit->log('auth.login_failed', meta: ['email' => strtolower($this->string('email'))]);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->status->canLogIn()) {
            Auth::guard('web')->logout();
            $audit->log('auth.login_blocked', $user, meta: ['status' => $user->status->value], userId: $user->id);

            throw ValidationException::withMessages([
                'email' => 'Your account is not active. Please contact GoVoyza support.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
