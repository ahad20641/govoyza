{{-- Visa finder results / all destinations. Public site. Task 1.13 — Home + visa finder. --}}
<x-layouts.public :title="$destination ? $destination->name.' visas' : 'Visa services'">

    {{-- ===== Heading ===== --}}
    <section class="page-head">
        <div class="container">
            <p class="crumbs"><a href="{{ route('home') }}">Home</a> / Visas</p>
            <h1>{{ $destination ? 'Visas for '.$destination->name : 'Visa services' }}</h1>
            @if ($nationality)
                <p class="muted">Showing services for {{ $nationalityLabel }} passports.</p>
            @endif
            <p><a href="{{ route('home') }}">Change search</a></p>
        </div>
    </section>

    {{-- ===== Results ===== --}}
    <section class="section">
        <div class="container">
            @forelse ($products as $product)
                @if ($loop->first)<div class="tiles">@endif
                @php
                    $price = $productPrices[$product->id];
                    $requirementLabels = $product->requirements->take(3)->pluck('label');
                @endphp
                <a class="tile" href="{{ route('visa.service', $product) }}">
                    <h3>{{ $product->public_name }}</h3>
                    <p>{{ $product->country->name }} · {{ $product->visaType->name }}
                        @if ($product->processing_days_max) · {{ $product->processing_days_min }}–{{ $product->processing_days_max }} days @endif</p>
                    <p><strong>{{ $price->total->format() }}</strong> total</p>
                    <p class="muted small">Service {{ $price->servicePrice->format() }} · Embassy {{ $price->embassyFee->format() }} · VAT {{ $price->vat->format() }}</p>
                    <p class="muted small">Documents: {{ $requirementLabels->isNotEmpty() ? $requirementLabels->implode(', ') : 'None listed' }}@if ($product->requirements->count() > 3) + {{ $product->requirements->count() - 3 }} more @endif</p>
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No visa services match yet. Try another destination or purpose.</p>
            @endforelse
        </div>
    </section>
</x-layouts.public>
