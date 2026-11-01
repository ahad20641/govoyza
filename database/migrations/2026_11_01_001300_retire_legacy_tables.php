<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint S1 — drops the inherited tables that the data model (planning/data-model.md) replaces.
 *
 * No code reads them, and `payments` / `ledger_accounts` block the new tables of the same name.
 * Staging held only test rows and production has never been migrated (decision 2026-11-01,
 * "Skeleton first"). One-way on purpose: down() does not bring the legacy schema back.
 */
return new class extends Migration
{
    /** Children before parents, so foreign keys never block a drop. */
    private const TABLES = [
        'receipts', 'payment_transactions', 'payments',
        'ledger_transactions', 'deposit_requests', 'ledger_accounts',
        'chat_messages', 'chat_participants', 'chat_conversations', 'reports',
        'courier_tracking', 'embassy_submissions', 'vendor_assignments',
        'application_status_history', 'application_documents', 'applications', 'application_drafts',
        'agency_vendor', 'vendor_services', 'agency_services', 'service_prices', 'services',
        'pickup_locations', 'visa_documents', 'visa_rules', 'authorized_agencies', 'vendors',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Intentionally empty: the legacy schema is retired, not paused.
    }
};
