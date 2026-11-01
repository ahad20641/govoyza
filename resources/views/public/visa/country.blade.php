{{-- Country page /visa/{country}, generated from the catalogue. Public site. Task 1.14 — Country pages. --}}
<x-layouts.public :title="$country->seo_title ?: $country->name.' visa'" :description="$country->seo_description">

    {{-- ===== Heading ===== --}}
    <section class="page-head">
        <div class="container">
            <p class="crumbs"><a href="{{ route('home') }}">Home</a> / <a href="{{ route('visa.index') }}">Visas</a> / {{ $country->name }}</p>
            <h1>{{ \App\Models\Country::flagFor($country->iso2) }} {{ $country->name }} visa</h1>
            @if ($country->description)<p class="muted prose">{{ $country->description }}</p>@endif
        </div>
    </section>

    {{-- ===== Visa types offered ===== --}}
    <section class="section">
        <div class="container">
            <h2>Visa options</h2>
            @forelse ($products as $product)
                @if ($loop->first)<div class="tiles">@endif
                <a class="tile" href="{{ route('visa.type', [$country, $product->visaType]) }}">
                    <h3>{{ $product->visaType->name }}</h3>
                    <p>{{ $product->public_name }}</p>
                </a>
                @if ($loop->last)</div>@endif
            @empty
                <p class="muted">We don't offer {{ $country->name }} visas yet.</p>
            @endforelse
            <x-coming-soon task="1.14">Entry requirements overview, FAQs, SEO meta, JSON-LD and sitemap entry.</x-coming-soon>
        </div>
    </section>
</x-layouts.public>
