<?php

/*
|--------------------------------------------------------------------------
| Permission catalogue
|--------------------------------------------------------------------------
| Synced into the `permissions` table by `php artisan govoyza:sync-permissions`
| (also run by the seeder). Super Admin implicitly has every permission via a
| Gate::before hook, so it is not listed per permission.
|
| Record-level ownership (a client sees only their own applications, an agency
| only its assigned requests) is enforced separately by Policies.
*/

return [
    // group => [slug => [label, [roles]]]
    'applications' => [
        'applications.create' => ['Create visa applications', ['client', 'agency']],
        'applications.view_own' => ['View own / assigned applications', ['client', 'agency', 'vendor']],
        'applications.view_any' => ['View all applications', []],
        'applications.update_status' => ['Change application status', ['agency', 'vendor']],
        'applications.verify_documents' => ['Verify application documents', []],
        'applications.refund' => ['Issue refunds', []],
    ],
    'b2b' => [
        'b2b.search_services' => ['Search visa services (B2B)', ['agency']],
        'b2b.assign_vendor' => ['Assign vendor to request', ['agency']],
        'b2b.manage_own_services' => ['Manage own agency services', ['agency']],
        'b2b.view_ledger' => ['View own ledger', ['agency']],
        'b2b.request_deposit' => ['Request deposit', ['agency']],
        'b2b.view_reports' => ['View own reports', ['agency']],
    ],
    'chat' => [
        'chat.use' => ['Use secure chat', ['client', 'agency', 'vendor']],
        'chat.audit' => ['Audit conversations', []],
    ],
    'admin' => [
        'admin.access' => ['Access admin panel', []],
        'clients.manage' => ['Manage clients', []],
        'agencies.manage' => ['Approve / suspend / manage agencies', []],
        'vendors.manage' => ['Manage vendors', []],
        'catalog.manage' => ['Manage countries, visa categories, types and rules', []],
        'pricing.manage' => ['Manage services & pricing', []],
        'payments.manage' => ['Manage payments', []],
        'ledger.manage' => ['Manage ledgers & deposits', []],
        'notifications.manage' => ['Manage notification templates', []],
        'reports.view_all' => ['View system reports', []],
        'settings.manage' => ['Manage system settings', []],
        'audit.view' => ['View audit logs', []],
    ],
];
