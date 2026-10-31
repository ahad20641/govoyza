@props(['href' => route('home')])
<a href="{{ $href }}" {{ $attributes->class('gv-logo') }} aria-label="GOVOYZA home">
    <span class="gv-logo__mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 16l20-8-8 14-2.5-6.5L2 16z"/></svg>
    </span>
    <span>GO<b>VOY</b>ZA</span>
</a>
