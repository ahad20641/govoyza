{{-- Agency sub-users. Agent portal /agent. Phase 2 — Sub-users. --}}
<x-layouts.agent title="Sub-users">

    {{-- ===== Team ===== --}}
    <div class="card">
        <h2>Your team</h2>
        <p style="margin:0">Owner: {{ $agency?->owner?->name ?? auth()->user()->name }}</p>
        @foreach ($users as $user)
            <p style="margin:0">{{ $user->name }} <span class="muted">{{ $user->email }}</span></p>
        @endforeach
    </div>
    <x-coming-soon task="2.x">Invite staff, set what each can do, and remove access.</x-coming-soon>
</x-layouts.agent>
