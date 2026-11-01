<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps staging private: HTTP Basic Auth on every host and path except the
 * /up health check, active only while config('govoyza.staging_gate.password_hash')
 * is set. Production never sets it.
 */
class StagingGate
{
    public const MAX_FAILURES_PER_MINUTE = 20;

    public function handle(Request $request, Closure $next): Response
    {
        $hash = config('govoyza.staging_gate.password_hash');

        if (! $hash || $request->is('up')) {
            return $next($request);
        }

        $key = 'staging-gate|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILURES_PER_MINUTE)) {
            return $this->deny(429, 'Too many attempts. Try again in a minute.');
        }

        $user = (string) $request->getUser();
        $password = (string) $request->getPassword();

        // Check both every time so a wrong user and a wrong password take the same time.
        $userOk = hash_equals((string) config('govoyza.staging_gate.user'), $user);
        $passwordOk = password_verify($password, $hash);

        if ($userOk && $passwordOk) {
            return $next($request);
        }

        // A browser's first request carries no credentials; only real guesses count.
        if ($user !== '' || $password !== '') {
            RateLimiter::hit($key, 60);
        }

        return $this->deny(401, 'This is a private test site.');
    }

    private function deny(int $status, string $message): Response
    {
        return response($message, $status, [
            'WWW-Authenticate' => 'Basic realm="GoVoyza staging", charset="UTF-8"',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
