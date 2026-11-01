{{-- Public site layout: header, mobile menu, footer. govoyza.com. Sprint S1 (content: tasks 1.13–1.16).
     Never link the partner portal from here (it is hidden: CLAUDE.md, planning/blueprint.md). --}}
@props(['title' => null, 'description' => null])
<x-layouts.base :title="$title" :description="$description">

    {{-- ===== Header ===== --}}
    <header class="site-header">
        <div class="container site-header__bar">
            <x-logo />
            <nav class="site-nav" aria-label="Main">
                <a href="{{ route('visa.index') }}" @class(['is-active' => request()->routeIs('visa.*')])>Visas</a>
                <a href="{{ route('pages.add-ons') }}" @class(['is-active' => request()->routeIs('pages.add-ons')])>Add-ons</a>
                <a href="{{ route('track') }}" @class(['is-active' => request()->routeIs('track*')])>Track application</a>
                <a href="{{ route('pages.about') }}" @class(['is-active' => request()->routeIs('pages.about')])>About</a>
                <a href="{{ route('pages.contact') }}" @class(['is-active' => request()->routeIs('pages.contact')])>Contact</a>
            </nav>
            <div class="site-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">My dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Get started</a>
                @endauth
            </div>

            {{-- ===== Mobile menu (no JavaScript: <details> works under the strict CSP) ===== --}}
            <details class="mnav">
                <summary aria-label="Open menu">Menu</summary>
                <nav class="mnav__panel" aria-label="Mobile">
                    <a href="{{ route('visa.index') }}">Visas</a>
                    <a href="{{ route('pages.add-ons') }}">Add-ons</a>
                    <a href="{{ route('track') }}">Track application</a>
                    <a href="{{ route('pages.about') }}">About</a>
                    <a href="{{ route('pages.contact') }}">Contact</a>
                    @auth
                        <a href="{{ route('dashboard') }}">My dashboard</a>
                    @else
                        <a href="{{ route('login') }}">Log in</a>
                        <a href="{{ route('register') }}">Get started</a>
                    @endauth
                </nav>
            </details>
        </div>
    </header>

    <main class="site-main">
        <div class="container"><x-alerts /></div>
        {{ $slot }}
    </main>

    {{-- ===== Footer ===== --}}
    <footer class="site-footer">
        <div class="container">
            <div class="site-footer__cols">
                <div>
                    <x-logo />
                    <p style="margin-top:14px">Visa applications handled online, from documents to decision.</p>
                </div>
                <div>
                    <h3>Visas</h3>
                    <ul>
                        <li><a href="{{ route('visa.index') }}">All destinations</a></li>
                        <li><a href="{{ route('pages.add-ons') }}">Add-ons</a></li>
                        <li><a href="{{ route('track') }}">Track application</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Company</h3>
                    <ul>
                        <li><a href="{{ route('pages.about') }}">About</a></li>
                        <li><a href="{{ route('pages.contact') }}">Contact</a></li>
                        <li><a href="{{ route('pages.faq') }}">FAQ</a></li>
                        <li><a href="{{ route('pages.become-an-agent') }}">Become an agent</a></li>
                    </ul>
                </div>
                <div>
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="{{ route('pages.terms') }}">Terms</a></li>
                        <li><a href="{{ route('pages.privacy') }}">Privacy</a></li>
                        <li><a href="{{ route('pages.refund-policy') }}">Refund policy</a></li>
                    </ul>
                </div>
            </div>
            <p class="site-footer__legal">
                © {{ now()->year }} {{ config('govoyza.company.name') }}. We do not guarantee visa approval: the decision is
                the embassy's. Our service fee covers processing and expertise; embassy fees are passed through at cost and
                shown separately.
            </p>
        </div>
    </footer>
</x-layouts.base>
