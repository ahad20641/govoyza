<x-layouts.auth title="Sign in">
    <h1>Welcome back</h1>
    <p class="muted">Sign in to manage your visa applications.</p>
    <x-alerts />
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autocomplete="email" autofocus />
        <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />
        <div style="display:flex;justify-content:space-between;align-items:center;margin:-4px 0 22px">
            <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
            <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    </form>
    <p class="auth__foot">New to GoVoyza? <a href="{{ route('register') }}">Create an account</a></p>
</x-layouts.auth>
