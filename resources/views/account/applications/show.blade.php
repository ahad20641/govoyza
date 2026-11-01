{{-- Application detail: timeline, checklist, uploads, messages. Customer portal /account.
     Tasks 1.22 (page), 1.18 (timeline), 1.19 (uploads). Never shows who processes it (CLAUDE.md rule 1). --}}
<x-layouts.account :title="'Application '.$order->number">

    {{-- ===== Summary ===== --}}
    <div class="stats">
        <div class="stat"><span>Visa</span><strong style="font-size:16px">{{ $order->product->public_name }}</strong></div>
        <div class="stat"><span>Status</span><strong style="font-size:16px">{{ $order->status->label() }}</strong></div>
        <div class="stat"><span>Total</span><strong style="font-size:16px">{{ $order->total?->format() }}</strong></div>
        <div class="stat"><span>Travel date</span><strong style="font-size:16px">{{ $order->travel_date?->format('j M Y') ?? '—' }}</strong></div>
    </div>

    <div class="two-col">
        <div>
            {{-- ===== Timeline ===== --}}
            <div class="card">
                <h2>Timeline</h2>
                {{-- Happy path only; cancelled/refunded orders show no step as done. --}}
                @php($steps = array_slice(\App\Enums\OrderStatus::cases(), 0, 9))
                @php($current = array_search($order->status, $steps, true))
                <ol class="timeline">
                    @foreach ($steps as $i => $step)
                        <li @class(['is-done' => $current !== false && $i <= $current])>{{ $step->label() }}</li>
                    @endforeach
                </ol>
                <x-coming-soon task="1.18">Dated history of every status change.</x-coming-soon>
            </div>

            {{-- ===== Documents ===== --}}
            <div class="card" style="margin-top:16px">
                <h2>Documents</h2>
                <x-coming-soon task="1.19">Checklist with upload, review status and reasons for anything flagged.</x-coming-soon>
            </div>

            {{-- ===== Messages ===== --}}
            <div class="card" style="margin-top:16px">
                <h2>Messages</h2>
                <x-coming-soon task="1.22">Messages with the GoVoyza team about this application.</x-coming-soon>
            </div>
        </div>

        {{-- ===== Applicants ===== --}}
        <aside class="card">
            <h2>Applicants</h2>
            <ul>
                @foreach ($order->applicants as $applicant)
                    <li>{{ $applicant->fullName() }}</li>
                @endforeach
            </ul>
        </aside>
    </div>
</x-layouts.account>
