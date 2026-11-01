{{-- Orders list used by the customer and agent portals. Shows Product data only, never the supplier.
     $link: route name taking the order number, or null for no link. Sprint S1. --}}
@props(['orders', 'link' => null])
@if ($orders->isEmpty())
    <div class="card empty">No applications yet. <a href="{{ route('visa.index') }}">Find a visa</a> to get started.</div>
@else
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Order</th><th>Visa</th><th>Status</th><th>Total</th><th>Placed</th></tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>@if ($link)<a href="{{ route($link, $order->number) }}">{{ $order->number }}</a>@else{{ $order->number }}@endif</td>
                        <td>{{ $order->product->public_name }}</td>
                        <td><span class="badge badge-gold">{{ $order->status->label() }}</span></td>
                        <td>{{ $order->total?->format() }}</td>
                        <td>{{ $order->placed_at?->format('j M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
