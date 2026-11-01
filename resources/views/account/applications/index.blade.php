{{-- Customer's applications list. Customer portal /account. Task 1.22 — Customer portal. --}}
<x-layouts.account title="Applications">

    {{-- ===== Applications ===== --}}
    <x-orders-table :orders="$orders" link="account.applications.show" />
    <div style="margin-top:16px">{{ $orders->links() }}</div>
</x-layouts.account>
