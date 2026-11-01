{{-- Visa finder results / all destinations. Public site. Task 1.13 — Home + visa finder. --}}
<x-layouts.public :title="$destination ? $destination->name.' visas' : 'Visa services'">

    {{-- ===== Heading ===== --}}
    <section class="page-head">
        <div class="container">
            <p class="crumbs"><a href="{{ route('home') }}">Home</a> / Visas</p>
            <h1>{{ $destination ? 'Visas for '.$destination->name : 'Visa services' }}</h1>
            <x-coming-soon task="1.13">Matching by your nationality, with prices and processing times.</x-coming-soon>
        </div>
    </section>

    {{-- ===== Results ===== --}}
    <section class="section">
        <div class="container">
            @forelse ($products as $product)
                @if ($loop->first)<div class="tiles">@endif
                <a class="tile" href="{{ route('visa.service', $product) }}">
                    <h3>{{ $product->public_name }}</h3>
                    <p>{{ $product->country->name }} · {{ $product->visaType->name }}
                        @if ($product->processing_days_max) · {{ $product->processing_days_min }}–{{ $product->processing_days_max }} days @endif</p>
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">No visa services match yet. Try another destination or purpose.</p>
            @endforelse
        </div>
    </section>
</x-layouts.public>
