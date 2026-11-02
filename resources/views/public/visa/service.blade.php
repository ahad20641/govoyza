{{-- Service detail: requirements, timeline, price, FAQs, Apply now. Public site.
     Tasks 1.14 (page), 1.11 (checklist), 1.12 (price), 1.17 (Apply now). Shows the Product only (CLAUDE.md rule 1). --}}
<x-layouts.public :title="$product->public_name" :description="\Illuminate\Support\Str::limit($product->description, 155)">

    {{-- ===== Heading ===== --}}
    <section class="page-head">
        <div class="container">
            <p class="crumbs">
                <a href="{{ route('home') }}">Home</a> / <a href="{{ route('visa.index') }}">Visas</a> /
                <a href="{{ route('visa.country', $product->country) }}">{{ $product->country->name }}</a> /
                <a href="{{ route('visa.type', [$product->country, $product->visaType]) }}">{{ $product->visaType->name }}</a>
            </p>
            <h1>{{ $product->public_name }}</h1>
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            <div>
                {{-- ===== About this service ===== --}}
                <div class="card">
                    <h2>About this service</h2>
                    <p>{{ $product->description ?: 'Full application support from document check to submission.' }}</p>
                    <p class="muted small" style="margin:0">
                        Processing:
                        {{ $product->processing_days_max ? $product->processing_days_min.'–'.$product->processing_days_max.' working days' : 'shown at checkout' }}
                        @if ($product->validity) · Validity: {{ $product->validity }} @endif
                    </p>
                </div>

                {{-- ===== Requirements checklist ===== --}}
                <div class="card" style="margin-top:16px">
                    <h2>Documents you'll need</h2>
                    @forelse ($product->requirements as $requirement)
                        @if ($loop->first)<ul>@endif
                        <li>
                            <strong>{{ $requirement->label }}</strong>
                            @unless ($requirement->is_required)<span class="muted">(optional)</span>@endunless
                            @if ($requirement->rules)
                                <span class="muted small">
                                    @if (! empty($requirement->rules['formats']))
                                        Formats: {{ implode(', ', array_map('strtoupper', $requirement->rules['formats'])) }}.
                                    @endif
                                    @if (! empty($requirement->rules['max_mb']))
                                        Maximum size: {{ $requirement->rules['max_mb'] }} MB.
                                    @endif
                                    @if (! empty($requirement->rules['passport_valid_months']))
                                        Passport validity: at least {{ $requirement->rules['passport_valid_months'] }} months.
                                    @endif
                                </span>
                            @endif
                        </li>
                        @if ($loop->last)</ul>@endif
                    @empty
                        <p class="muted">No document requirements have been published for this visa yet.</p>
                    @endforelse
                </div>

                {{-- ===== FAQs ===== --}}
                <div class="card" style="margin-top:16px">
                    <h2>FAQs</h2>
                    <x-coming-soon task="1.14">Questions customers ask about this visa.</x-coming-soon>
                </div>
            </div>

            {{-- ===== Price and Apply now ===== --}}
            <aside class="card">
                <h2>Price</h2>
                <x-coming-soon task="1.12">Service fee, embassy fee (at cost, shown separately), VAT and total.</x-coming-soon>
                <p class="muted small">Embassy fee: {{ $product->embassy_fee?->format() ?? 'shown at checkout' }}</p>
                <button type="button" class="btn btn-primary btn-block" disabled>Apply now</button>
                <p class="muted small" style="margin-top:10px">Online ordering opens with task 1.17. We do not guarantee visa approval.</p>
            </aside>
        </div>
    </section>
</x-layouts.public>
