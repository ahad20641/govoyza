{{-- Visa-type page /visa/{country}/{type}. Public site. Task 1.14 — Visa-type pages. --}}
<x-layouts.public :title="$country->name.' '.$visaType->name.' visa'">

    {{-- ===== Heading ===== --}}
    <section class="page-head">
        <div class="container">
            <p class="crumbs">
                <a href="{{ route('home') }}">Home</a> / <a href="{{ route('visa.index') }}">Visas</a> /
                <a href="{{ route('visa.country', $country) }}">{{ $country->name }}</a> / {{ $visaType->name }}
            </p>
            <h1>{{ $country->name }} {{ $visaType->name }} visa</h1>
        </div>
    </section>

    {{-- ===== Services ===== --}}
    <section class="section">
        <div class="container">
            @forelse ($products as $product)
                @if ($loop->first)<div class="tiles">@endif
                <a class="tile" href="{{ route('visa.service', $product) }}">
                    <h3>{{ $product->public_name }}</h3>
                    <p>{{ $product->validity ?: 'Validity on the service page' }}</p>
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">This visa type isn't available for {{ $country->name }} yet.</p>
            @endforelse
            <x-coming-soon task="1.14">Eligibility, typical timeline and FAQs for this visa type.</x-coming-soon>
        </div>
    </section>
</x-layouts.public>
