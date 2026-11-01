{{-- Profile and notification settings. Customer portal /account. Task 1.22 — Customer portal. --}}
<x-layouts.account title="Profile">

    {{-- ===== Details ===== --}}
    <div class="card">
        <h2>Your details</h2>
        <p style="margin:0">{{ $user->name }}<br><span class="muted">{{ $user->email }}</span>@if ($user->phone)<br><span class="muted">{{ $user->phone }}</span>@endif</p>
    </div>

    {{-- ===== Settings ===== --}}
    <x-coming-soon task="1.22">Edit profile, change password and choose email/SMS notifications.</x-coming-soon>
</x-layouts.account>
