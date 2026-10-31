<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out suspended/rejected users on their next request, and routes pending
 * accounts (agencies awaiting approval) to the pending page.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->status->canLogIn()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is not active. Please contact GoVoyza support.',
            ]);
        }

        if ($user->status === UserStatus::Pending && ! $request->routeIs('account.pending', 'logout', 'verification.*')) {
            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
