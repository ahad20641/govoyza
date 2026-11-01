<x-layouts.auth title="Verify your email">
    <div class="choice__icon" aria-hidden="true" style="margin-bottom:18px">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
    </div>
    <h1>You're almost done!</h1>
    <p class="muted">We've sent a 6-digit code to <strong style="color:var(--gv-ink)">{{ auth()->user()->email }}</strong>. Enter it below to activate your account. It expires in {{ $minutes }} minutes.</p>
    <x-alerts />
    <form method="POST" action="{{ route('verification.verify') }}" novalidate>
        @csrf
        <x-form.input name="code" label="Verification code" required autocomplete="one-time-code"
            inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autofocus />
        <button type="submit" class="btn btn-primary btn-block">Verify email</button>
    </form>
    <form method="POST" action="{{ route('verification.send') }}" style="margin-top:10px">
        @csrf
        <button type="submit" class="btn btn-outline btn-block">Send a new code</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:10px">
        @csrf
        <button type="submit" class="btn btn-ghost btn-block">Sign out</button>
    </form>
</x-layouts.auth>
