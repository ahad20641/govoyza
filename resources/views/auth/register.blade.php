<x-layouts.auth title="Create account">
    <h1>Create your account</h1>
    <p class="muted">Choose the account that fits you. It only takes a minute.</p>
    <div style="display:grid;gap:14px;margin-top:22px">
        <a class="choice" href="{{ route('register.client') }}">
            <div class="choice__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
            </div>
            <h3>Create Client Account</h3>
            <p>Apply for a visa for yourself or your family, pay online and track your application.</p>
        </a>
        <a class="choice" href="{{ route('register.agency') }}">
            <div class="choice__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M3 13h18"/></svg>
            </div>
            <h3>Register Travel Agency <span class="badge badge-gold" style="margin-left:6px">B2B</span></h3>
            <p>Process visa requests for your customers, manage vendors, ledger and reports. Requires admin approval.</p>
        </a>
    </div>
    <p class="auth__foot">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
</x-layouts.auth>
