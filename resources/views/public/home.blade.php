{{-- Home page with the visa finder. Public site. Task 1.13 — Home + visa finder (S1: form UI only). --}}
<x-layouts.public title="Your visa journey, simplified" description="Apply for visas online: check requirements, upload documents, pay securely and track your application.">

    {{-- ===== Hero + visa finder ===== --}}
    <section class="hero">
        <div class="container">
            <span class="badge badge-gold">Visa services from Bangladesh</span>
            <h1 style="margin-top:14px">Your visa journey, simplified.</h1>
            <p class="lead">Tell us where you're going. We check your documents, prepare your application and keep you updated until your visa is decided.</p>

            <form class="finder" method="GET" action="{{ route('visa.index') }}" aria-label="Visa finder">
                <div class="field">
                    <label for="nationality">I am a citizen of</label>
                    <select id="nationality" name="nationality" class="input">
                        @foreach ($nationalities as $nationality)
                            <option value="{{ $nationality->country?->iso2 }}">{{ $nationality->demonym }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="destination">Going to</label>
                    <select id="destination" name="destination" class="input">
                        <option value="">Any destination</option>
                        @foreach ($destinations as $country)
                            <option value="{{ $country->slug }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="purpose">For</label>
                    <select id="purpose" name="purpose" class="input">
                        <option value="">Any purpose</option>
                        @foreach ($visaTypes as $type)
                            <option value="{{ $type->slug }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Find my visa</button>
            </form>
            <x-coming-soon task="1.13">Results matched to your nationality, with price, processing time and requirements.</x-coming-soon>
        </div>
    </section>

    {{-- ===== Popular destinations ===== --}}
    <section class="section">
        <div class="container">
            <h2>Popular destinations</h2>
            @if ($popular->isEmpty())
                <p class="muted">Destinations appear here once their visa services are published.</p>
            @else
                <div class="tiles">
                    @foreach ($popular as $country)
                        <a class="tile" href="{{ route('visa.country', $country) }}">
                            <h3>{{ \App\Models\Country::flagFor($country->iso2) }} {{ $country->name }}</h3>
                            <p>See visa options</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ===== How it works ===== --}}
    <section class="section" style="background:var(--gv-surface)">
        <div class="container">
            <h2>How it works</h2>
            <div class="tiles">
                <div class="tile"><h3>1. Choose your visa</h3><p>See requirements, timeline and the full price up front.</p></div>
                <div class="tile"><h3>2. Upload documents</h3><p>We check every file before anything is submitted.</p></div>
                <div class="tile"><h3>3. Pay securely</h3><p>Card, bKash or Nagad. Embassy fees shown separately.</p></div>
                <div class="tile"><h3>4. Track to decision</h3><p>Status updates by email at every step.</p></div>
            </div>
        </div>
    </section>
</x-layouts.public>
