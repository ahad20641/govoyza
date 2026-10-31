{{-- Temporary landing page — replaced by the full public website in Phase 2. --}}
<x-layouts.base title="Your Visa Journey, Simplified">
    <main style="min-height:100vh;display:grid;place-items:center;padding:24px;text-align:center">
        <div>
            <x-logo />
            <h1 style="margin-top:28px">Your Visa Journey, Simplified.</h1>
            <p class="muted" style="max-width:560px;margin:0 auto 24px">Apply for visa services online, upload your documents securely, make payments and track your application from anywhere.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
                    <a href="{{ route('login') }}" class="btn btn-outline">Login</a>
                @endauth
            </div>
        </div>
    </main>
</x-layouts.base>
