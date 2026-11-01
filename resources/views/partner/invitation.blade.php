{{-- Partner invite link. Partner portal pages never link to the public site and the public site never links here. --}}
<x-layouts.base title="Create your partner account">
    <main class="auth__main" style="min-height:100vh">
        <div class="auth__panel">
            <x-logo href="#" />
            @if($invitation)
                <h1 style="margin-top:24px">Create your partner account</h1>
                <p class="muted">For <strong style="color:var(--gv-ink)">{{ $invitation->email }}</strong>. After this you'll set up
                    two-factor sign-in, upload your KYC documents, add bank details and sign the partner agreement.
                    GoVoyza then reviews and activates your account.</p>
                <x-alerts />
                <form method="POST" action="{{ route('filament.partner.invitation.accept', ['token' => $token]) }}" novalidate>
                    @csrf
                    <x-form.input name="legal_name" label="Company legal name" required :value="$invitation->legal_name" autocomplete="organization" />
                    <div class="grid-2">
                        <x-form.input name="name" label="Your full name" required autocomplete="name" />
                        <x-form.input name="phone" type="tel" label="Phone (optional)" autocomplete="tel" />
                    </div>
                    <div class="grid-2">
                        <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" />
                        <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Create account</button>
                </form>
            @else
                <h1 style="margin-top:24px">This link is no longer valid</h1>
                <p class="muted">Invitation links work once and expire after {{ config('govoyza.partner.invitation_days') }} days.
                    Ask your GoVoyza contact for a new one.</p>
            @endif
        </div>
    </main>
</x-layouts.base>
