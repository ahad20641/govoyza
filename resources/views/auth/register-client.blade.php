<x-layouts.auth title="Create client account">
    <h1>Create Client Account</h1>
    <p class="muted">We'll use your email to send application updates and receipts.</p>
    <x-alerts />
    <form method="POST" action="{{ route('register.client') }}" novalidate>
        @csrf
        <x-form.input name="name" label="Full name" required autocomplete="name" hint="As written on your passport." />
        <div class="grid-2">
            <x-form.input name="email" type="email" label="Email address" required autocomplete="email" />
            <x-form.input name="phone" type="tel" label="Phone (optional)" autocomplete="tel" />
        </div>
        @if($nationalities->isNotEmpty())
            <div class="field">
                <label for="f_nationality_id">Passport nationality <span class="muted small">(optional)</span></label>
                <select id="f_nationality_id" name="nationality_id" class="input">
                    <option value="">Select later</option>
                    @foreach($nationalities as $n)
                        <option value="{{ $n->id }}" @selected(old('nationality_id') == $n->id)>{{ $n->country->name }}</option>
                    @endforeach
                </select>
                @error('nationality_id')<div class="error">{{ $message }}</div>@enderror
            </div>
        @endif
        <div class="grid-2">
            <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" hint="At least 8 characters with letters and numbers." />
            <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
        </div>
        <div class="field">
            <label class="check"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I agree to the Terms of Service and Privacy Policy.</label>
            @error('terms')<div class="error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>
    <p class="auth__foot">Registering a business? <a href="{{ route('register.agency') }}">Register a travel agency</a></p>
</x-layouts.auth>
