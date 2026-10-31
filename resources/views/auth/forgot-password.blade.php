<x-layouts.auth title="Forgot password">
    <h1>Reset your password</h1>
    <p class="muted">Enter your account email and we'll send you a secure reset link.</p>
    <x-alerts />
    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" autofocus />
        <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>
    <p class="auth__foot"><a href="{{ route('login') }}">← Back to sign in</a></p>
</x-layouts.auth>
