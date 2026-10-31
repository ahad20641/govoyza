<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->authenticate($audit);

        // Prevent session fixation.
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();
        $audit->log('auth.login', $user, userId: $user->id);

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->log('auth.logout');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }
}
