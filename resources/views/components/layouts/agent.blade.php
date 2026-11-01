{{-- Agent portal (/agent) layout with its navigation. Phase 2 — Agent portal (shells in Sprint S1). --}}
@props(['title'])
<x-layouts.portal portal="Agent" :title="$title" :nav="[
    'agent.dashboard' => 'Dashboard',
    'agent.prices.index' => 'Price list',
    'agent.applications.create' => 'New application',
    'agent.applications.bulk' => 'Bulk application',
    'agent.wallet.show' => 'Wallet',
    'agent.users.index' => 'Sub-users',
    'agent.reports.index' => 'Reports',
]">
    {{ $slot }}
</x-layouts.portal>
