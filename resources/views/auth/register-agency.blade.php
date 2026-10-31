<x-layouts.auth title="Register travel agency" :wide="true">
    <span class="badge badge-gold">B2B Partner</span>
    <h1 style="margin-top:10px">Register Travel Agency</h1>
    <p class="muted">Tell us about your business. Our team reviews every agency before activating B2B access — usually within 1–2 business days.</p>
    <x-alerts />
    <form method="POST" action="{{ route('register.agency') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="section-label">1 · Business details</div>
        <x-form.input name="agency_name" label="Agency name" required autocomplete="organization" />
        <div class="grid-2">
            <x-form.input name="trade_license_number" label="Trade license number" required />
            <x-form.input name="website" type="url" label="Website (optional)" placeholder="https://" />
            <x-form.input name="business_email" type="email" label="Business email" required />
            <x-form.input name="business_phone" type="tel" label="Business phone" required />
        </div>
        <div class="field">
            <label for="f_address">Office address <span class="req" aria-hidden="true">*</span></label>
            <textarea id="f_address" name="address" rows="2" required @class(['input', 'is-invalid' => $errors->has('address')])>{{ old('address') }}</textarea>
            @error('address')<div class="error">{{ $message }}</div>@enderror
        </div>
        @if($districts->isNotEmpty())
            <div class="field">
                <label for="f_district_id">District</label>
                <select id="f_district_id" name="district_id" class="input">
                    <option value="">Select district</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}" @selected(old('district_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                @error('district_id')<div class="error">{{ $message }}</div>@enderror
            </div>
        @endif

        <div class="section-label">2 · Verification documents</div>
        <p class="small muted" style="margin-top:-6px">Documents are stored privately and seen only by GoVoyza's verification team.</p>
        <div class="grid-2">
            @foreach($documents as $key => $doc)
                <x-form.file name="documents[{{ $key }}]" :label="$doc['label']" :required="$doc['required']" />
            @endforeach
        </div>

        <div class="section-label">3 · Account owner</div>
        <x-form.input name="name" label="Your full name" required autocomplete="name" />
        <div class="grid-2">
            <x-form.input name="email" type="email" label="Login email" required autocomplete="email" />
            <x-form.input name="phone" type="tel" label="Mobile number" required autocomplete="tel" />
            <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" />
            <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
        </div>
        <div class="field">
            <label class="check"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I confirm the information is accurate and agree to the B2B Partner Terms.</label>
            @error('terms')<div class="error">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary btn-block">Submit for approval</button>
        <p class="small muted" style="text-align:center;margin-top:12px">What happens next: verify your email → we review your documents → you get an approval email.</p>
    </form>
    <p class="auth__foot">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
</x-layouts.auth>
