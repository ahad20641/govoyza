{{-- B2B net price list. Agent portal /agent. Task 1.12 (net prices), Phase 2 — Agent portal. --}}
<x-layouts.agent title="Price list">

    <x-coming-soon task="1.12">Your net price per service for your tier, in BDT with USD/EUR alongside.</x-coming-soon>

    {{-- ===== Live services ===== --}}
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Service</th><th>Country</th><th>Type</th><th>Processing</th><th>Net price</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>{{ $product->public_name }}</td>
                        <td>{{ $product->country->name }}</td>
                        <td>{{ $product->visaType->name }}</td>
                        <td>{{ $product->processing_days_max ? $product->processing_days_min.'–'.$product->processing_days_max.' days' : '—' }}</td>
                        <td class="muted">—</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No live services yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px">{{ $products->links() }}</div>
</x-layouts.agent>
