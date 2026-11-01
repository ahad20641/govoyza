{{-- Customer portal (/account) layout with its navigation. Task 1.22 — Customer portal. --}}
@props(['title'])
<x-layouts.portal portal="Customer" :title="$title" :nav="[
    'account.dashboard' => 'Dashboard',
    'account.applications.index' => 'Applications',
    'account.travellers.index' => 'Saved travellers',
    'account.payments.index' => 'Payments & invoices',
    'account.support.index' => 'Support',
    'account.profile.show' => 'Profile',
]">
    {{ $slot }}
</x-layouts.portal>
