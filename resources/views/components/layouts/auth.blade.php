@props(['title' => null, 'wide' => false])
<x-layouts.base :title="$title">
    <div class="auth">
        <aside class="auth__aside">
            <x-logo />
            <div class="auth__quote">
                <h2>Your visa journey, <em>simplified.</em></h2>
                <p>Apply online, upload documents securely, pay in full and track every step — from anywhere.</p>
                <ul class="auth__points">
                    <li>Bank-grade encrypted document storage</li>
                    <li>Transparent pricing — no hidden fees</li>
                    <li>Real-time application tracking</li>
                </ul>
            </div>
            <p class="small" style="color:rgba(255,255,255,.45);position:relative;z-index:1">© {{ date('Y') }} GOVOYZA</p>
        </aside>
        <main class="auth__main">
            <div class="auth__top">
                <x-logo />
                <a href="{{ route('home') }}" class="small muted">← Back to site</a>
            </div>
            <div @class(['auth__panel', 'auth__panel--wide' => $wide])>
                {{ $slot }}
            </div>
        </main>
    </div>
</x-layouts.base>
