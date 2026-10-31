<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route($request->user()->dashboardRoute())
            : view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route($request->user()->dashboardRoute())->with('status', 'Your email has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'A new verification link has been sent to your email.');
    }
}
