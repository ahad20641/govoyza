{{-- Logged-in portal shell for /account (customer) and /agent (agency). Sprint S1.
     $nav: route name => label. A link is active for its route and any child route. --}}
@props(['portal', 'title', 'nav' => []])
<x-layouts.base :title="$title">
    <div class="shell">

        {{-- ===== Sidebar (desktop) ===== --}}
        <aside class="sidebar">
            <x-logo :href="route('home')" />
            <nav class="nav" aria-label="{{ $portal }} navigation">
                @foreach ($nav as $route => $label)
                    <a href="{{ route($route) }}" @class(['is-active' => request()->routeIs($route, str_replace(['.index', '.show'], '', $route).'.*')])>{{ $label }}</a>
                @endforeach
            </nav>
        </aside>

        <main class="main">
            {{-- ===== Top bar ===== --}}
            <div class="topbar">
                <div>
                    <span class="badge badge-gold">{{ $portal }} portal</span>
                    <h1 style="margin-top:8px">{{ $title }}</h1>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">Sign out</button>
                </form>
            </div>

            {{-- ===== Mobile menu ===== --}}
            <details class="portal-menu">
                <summary>Menu</summary>
                <nav class="nav" aria-label="{{ $portal }} navigation (mobile)">
                    @foreach ($nav as $route => $label)
                        <a href="{{ route($route) }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </details>

            <x-alerts />

            {{-- ===== Page content ===== --}}
            {{ $slot }}
        </main>
    </div>
</x-layouts.base>
