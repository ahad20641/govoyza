<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\OneTimePasswords;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route($request->user()->dashboardRoute())
            : view('auth.verify-email', ['minutes' => OneTimePasswords::TTL_MINUTES]);
    }

    public function verify(Request $request, OneTimePasswords $otp, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route($user->dashboardRoute());
        }

        $code = $request->validate(['code' => ['required', 'digits:6']])['code'];
        $result = $otp->verify($user, OtpPurpose::EmailVerification, $code);

        if ($result !== OtpResult::Valid) {
            $audit->log('auth.email_code_failed', $user, meta: ['result' => $result->value], userId: $user->id);

            return back()->withErrors(['code' => $result->message()]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));
        $audit->log('auth.email_verified', $user, userId: $user->id);

        return redirect()->route($user->dashboardRoute())->with('status', 'Your email has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'A new code has been sent to your email.');
    }
}
