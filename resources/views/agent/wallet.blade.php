{{-- Agency wallet. Agent portal /agent. Phase 2 — Wallet and credit. --}}
<x-layouts.agent title="Wallet">

    {{-- ===== Balance ===== --}}
    <div class="stats">
        <div class="stat"><span>Balance</span><strong>{{ $balance?->format() ?? '—' }}</strong></div>
        <div class="stat"><span>Credit limit</span><strong>{{ $agency?->credit_limit?->format() ?? '—' }}</strong></div>
    </div>
    <x-coming-soon task="2.x">Top up by bank, bKash or card; statement download.</x-coming-soon>

    {{-- ===== Statement ===== --}}
    <h2>Statement</h2>
    @if ($transactions->isEmpty())
        <div class="card empty">No wallet movements yet.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance</th></tr></thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('j M Y') }}</td>
                            <td>{{ $transaction->type->label() }}</td>
                            <td>{{ $transaction->amount->format() }}</td>
                            <td>{{ $transaction->balance_after->format() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.agent>
