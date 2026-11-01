{{-- Agency dashboard. Agent portal /agent. Phase 2 — Agent portal (S1 shell). --}}
<x-layouts.agent title="Agency dashboard">

    {{-- ===== Key figures ===== --}}
    <div class="stats">
        <div class="stat"><span>Agency</span><strong style="font-size:16px">{{ $agency?->name ?? '—' }}</strong></div>
        <div class="stat"><span>Tier</span><strong style="font-size:16px">{{ $agency?->tier?->label() ?? '—' }}</strong></div>
        <div class="stat"><span>Wallet</span><strong style="font-size:16px">{{ $balance?->format() ?? '—' }}</strong></div>
        <div class="stat"><span>Spend this month</span><strong style="font-size:16px">—</strong></div>
    </div>
    <x-coming-soon task="2.x">Orders by status, monthly spend and wallet alerts.</x-coming-soon>

    {{-- ===== Recent orders ===== --}}
    <h2>Recent orders</h2>
    <x-orders-table :orders="$orders" />
</x-layouts.agent>
