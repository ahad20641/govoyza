{{-- Support. Customer portal /account. Task 1.22 — Customer portal. --}}
<x-layouts.account title="Support">

    {{-- ===== Contact ===== --}}
    <div class="card">
        <h2>Need help?</h2>
        <p>Email <a href="mailto:{{ config('govoyza.company.support_email') }}">{{ config('govoyza.company.support_email') }}</a> with your order number.</p>
    </div>

    {{-- ===== Tickets ===== --}}
    <x-coming-soon task="1.22">Support tickets and message threads per application.</x-coming-soon>
</x-layouts.account>
