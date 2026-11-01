<?php

/*
|--------------------------------------------------------------------------
| Permission catalogue
|--------------------------------------------------------------------------
| Synced into the `permissions` table by `php artisan govoyza:sync-permissions`
| (also run by the seeder). Owner implicitly has every permission via a
| Gate::before hook, so it is not listed per permission.
|
| Staff roles are least-privilege (planning/security.md):
| - Operations runs orders, documents, partners and the catalogue. No money.
| - Finance handles payments, refunds, ledger and reports. It can view orders
|   but cannot edit them.
| - Owner alone manages pricing, staff, settings and the audit log.
|
| Record-level ownership (a client sees only their own applications, an agency
| only its assigned requests) is enforced separately by Policies.
*/

return [
    // group => [slug => [label, [roles]]]
    'applications' => [
        'applications.create' => ['Create visa applications', ['client', 'agency']],
        'applications.view_own' => ['View own / assigned applications', ['client', 'agency', 'partner']],
        'applications.view_any' => ['View all applications', ['operations', 'finance']],
        'applications.update_status' => ['Change application status', ['agency', 'partner', 'operations']],
        'applications.verify_documents' => ['Verify application documents', ['operations']],
        'applications.refund' => ['Issue refunds', ['finance']],
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
        'chat.use' => ['Use secure chat', ['client', 'agency', 'partner']],
        'chat.audit' => ['Audit conversations', ['operations']],
    ],
    'admin' => [
        'admin.access' => ['Access admin panel', ['operations', 'finance']],
        'staff.manage' => ['Manage staff accounts and roles', []],
        'clients.manage' => ['Manage clients', ['operations']],
        'agencies.manage' => ['Approve / suspend / manage agencies', ['operations']],
        'partners.manage' => ['Invite, approve and manage partners (suppliers)', ['operations']],
        'catalog.manage' => ['Manage countries, visa categories, types and rules', ['operations']],
        'pricing.manage' => ['Manage services & pricing', []],
        'exchange_rates.manage' => ['Set exchange rates and buffers', ['finance']],
        'payments.manage' => ['Manage payments', ['finance']],
        'ledger.manage' => ['Manage ledgers & deposits', ['finance']],
        'notifications.manage' => ['Manage notification templates', ['operations']],
        'cms.manage' => ['Manage CMS pages and blog posts', ['operations']],
        'reports.view_all' => ['View system reports', ['finance']],
        'settings.manage' => ['Manage system settings', []],
        'audit.view' => ['View audit logs', []],
    ],
];
