{{-- Phase 1 portal shell. Navigation and content are filled in per phase. --}}
<x-layouts.base :title="$title">
    <div class="shell">
        <aside class="sidebar">
            <x-logo :href="route('dashboard')" />
            <nav class="nav" aria-label="{{ $portal }} navigation">
                <a href="{{ route('dashboard') }}" class="is-active">Dashboard</a>
            </nav>
        </aside>
        <main class="main">
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
            <x-alerts />
            <div class="card">
                <h2>Welcome, {{ auth()->user()->name }}</h2>
                <p class="muted" style="margin:0">Your {{ strtolower($portal) }} workspace is set up. Features for this portal arrive in upcoming build phases.</p>
            </div>
        </main>
        <nav class="bottom-nav" aria-label="Mobile navigation">
            <a href="{{ route('dashboard') }}" class="is-active">Home</a>
        </nav>
    </div>
</x-layouts.base>
