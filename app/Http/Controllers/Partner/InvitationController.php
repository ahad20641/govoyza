<?php

namespace App\Http\Controllers\Partner;

use App\Domain\Partners\PartnerInvitations;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/** Invite link → partner account. Routes live in PartnerPanelProvider (guest routes on the partner domain). */
class InvitationController extends Controller
{
    public function __construct(private readonly PartnerInvitations $invitations) {}

    public function show(string $token): Response
    {
        $invitation = $this->invitations->find($token);

        return response()->view('partner.invitation', compact('invitation', 'token'), $invitation ? 200 : 410);
    }

    public function accept(Request $request, string $token): RedirectResponse|Response
    {
        $invitation = $this->invitations->find($token);

        if (! $invitation) {
            return $this->show($token);
        }

        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (User::withTrashed()->where('email', $invitation->email)->exists()) {
            return back()->withErrors(['legal_name' => 'An account with this email already exists. Ask GoVoyza to link it instead.']);
        }

        $user = $this->invitations->accept($invitation, $data);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // Next stop: the panel's required 2FA set-up, then onboarding.
        return redirect()->to(Filament::getPanel('partner')->getUrl());
    }
}
