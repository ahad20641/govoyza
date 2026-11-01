{{-- Saved travellers. Customer portal /account. Task 1.22 — Customer portal.
     Passport numbers are never listed in full (encrypted at rest, CLAUDE.md rule 6). --}}
<x-layouts.account title="Saved travellers">

    {{-- ===== Travellers ===== --}}
    @if ($travellers->isEmpty())
        <div class="card empty">No saved travellers yet.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Name</th><th>Nationality</th><th>Passport</th><th>Expires</th></tr></thead>
                <tbody>
                    @foreach ($travellers as $traveller)
                        <tr>
                            <td>{{ $traveller->fullName() }}</td>
                            <td>{{ $traveller->nationality_iso2 }}</td>
                            <td>•••• {{ substr((string) $traveller->passport_number, -3) }}</td>
                            <td>{{ $traveller->passport_expiry?->format('j M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <x-coming-soon task="1.22">Add, edit and remove travellers; reuse them when you apply.</x-coming-soon>
</x-layouts.account>
