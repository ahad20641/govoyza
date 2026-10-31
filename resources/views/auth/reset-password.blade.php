<x-layouts.auth title="Choose a new password">
    <h1>Choose a new password</h1>
    <x-alerts />
    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email address" required :value="$email" autocomplete="email" />
        <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
        <button type="submit" class="btn btn-primary btn-block">Reset password</button>
    </form>
</x-layouts.auth>
