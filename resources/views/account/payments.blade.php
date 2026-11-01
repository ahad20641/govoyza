{{-- Payments and invoices. Customer portal /account. Tasks 1.23 (payments), 1.25 (invoice PDFs). --}}
<x-layouts.account title="Payments & invoices">

    {{-- ===== Payments ===== --}}
    @if ($payments->isEmpty())
        <div class="card empty">No payments yet.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Order</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td>{{ $payment->order?->number }}</td>
                            <td>{{ $payment->amount->format() }}</td>
                            <td>{{ $payment->status->label() }}</td>
                            <td>{{ $payment->created_at->format('j M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ===== Invoices ===== --}}
    <x-coming-soon task="1.25">Invoice and receipt PDFs to download, credit notes for refunds.</x-coming-soon>
</x-layouts.account>
