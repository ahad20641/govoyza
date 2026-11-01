{{-- Track application by order number + email, no login. Public site. Task 1.16 — Track application. --}}
<x-layouts.public title="Track your application">

    {{-- ===== Heading ===== --}}
    <section class="page-head">
        <div class="container">
            <p class="crumbs"><a href="{{ route('home') }}">Home</a> / Track application</p>
            <h1>Track your application</h1>
            <p class="muted">Enter the order number from your confirmation email and the email you used to apply.</p>
        </div>
    </section>

    {{-- ===== Lookup form ===== --}}
    <section class="section">
        <div class="container" style="max-width:560px;margin-left:clamp(16px,3vw,32px)">
            <form method="POST" action="{{ route('track.lookup') }}" class="card">
                @csrf
                <x-form.input name="number" label="Order number" placeholder="GV-2611-ABC123" required />
                <x-form.input name="email" type="email" label="Email" required />
                <button type="submit" class="btn btn-primary btn-block">Track</button>
            </form>
            <x-coming-soon task="1.16">Live status timeline for your order.</x-coming-soon>
        </div>
    </section>
</x-layouts.public>
