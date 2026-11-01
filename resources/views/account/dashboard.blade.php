{{-- Customer dashboard. Customer portal /account. Task 1.22 — Customer portal. --}}
<x-layouts.account title="My dashboard">

    {{-- ===== Welcome ===== --}}
    <div class="card" style="margin-bottom:20px">
        <h2>Welcome, {{ auth()->user()->name }}</h2>
        <p class="muted" style="margin:0">Your applications, documents and payments in one place.</p>
    </div>

    {{-- ===== What needs doing ===== --}}
    <x-coming-soon task="1.22">Status bars per application and the next step you need to take (upload, pay, book).</x-coming-soon>

    {{-- ===== Recent applications ===== --}}
    <h2>Recent applications</h2>
    <x-orders-table :orders="$orders" link="account.applications.show" />
</x-layouts.account>
