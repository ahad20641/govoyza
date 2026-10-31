<x-layouts.auth title="Application under review">
    <span class="badge badge-warning">Pending approval</span>
    <h1 style="margin-top:12px">Thanks{{ $agency ? ', '.$agency->name : '' }}!</h1>
    <p class="muted">Your agency registration is being reviewed by our verification team. We'll email you as soon as your B2B account is approved.</p>
    <div class="card" style="padding:20px;margin:22px 0">
        <ol style="margin:0;padding-left:18px;display:grid;gap:8px" class="small">
            <li><strong>Registration received</strong> <span class="muted">— done</span></li>
            <li><strong>Email verified</strong> <span class="muted">— done</span></li>
            <li><strong>Document review</strong> <span class="muted">— in progress</span></li>
            <li><strong>B2B access activated</strong></li>
        </ol>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-ghost btn-block">Sign out</button>
    </form>
</x-layouts.auth>
