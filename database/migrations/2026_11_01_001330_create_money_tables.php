<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint S1 — payments, agent wallet, double-entry ledger and partner payouts
 * (planning/data-model.md, planning/accounting-rules.md). Money is *_minor BIGINT + currency.
 * Posting rules, the balanced-entry guard and immutability arrive with task 1.24.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ==================== Payments (task 1.23) ====================
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->restrictOnDelete(); // wallet top-ups
            $table->string('gateway', 30);                   // sslcommerz | stripe | bank | wallet
            $table->string('gateway_ref')->nullable()->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 20)->default('pending')->index();
            $table->json('raw')->nullable();                 // gateway payload, kept for disputes
            $table->string('idempotency_key', 100)->unique();
            $table->timestamps();
        });

        // ==================== Agent wallet (Phase 2) ====================
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);                      // topup | order | refund | adjust
            // Signed: orders are negative, top-ups positive; the balance may dip below zero
            // down to the agency's credit limit.
            $table->bigInteger('amount_minor');
            $table->bigInteger('balance_after_minor');
            $table->char('currency', 3);
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        // ==================== Ledger (task 1.24) ====================
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 20);                      // asset | liability | equity | revenue | expense
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->timestamp('posted_at');
            $table->string('event', 60)->index();            // payment.received, order.assigned, …
            $table->nullableMorphs('reference');
            $table->text('memo')->nullable();
            $table->foreignId('reversed_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('debit_minor')->default(0);
            $table->unsignedBigInteger('credit_minor')->default(0);
            $table->char('currency', 3);
            $table->foreignId('partner_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
            // TODO(1.24): DB CHECK that exactly one of debit/credit is > 0 (MySQL only; SQLite tests use the app guard).
        });

        // ==================== Partner payouts (task 1.27, Phase 2 batch) ====================
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->string('period', 20);                    // e.g. 2026-W45
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->char('currency', 3);
            $table->string('status', 20)->default('draft');  // draft | approved | paid
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['partner_id', 'period', 'currency']);
        });

        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_job_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payout_items', 'payouts', 'journal_lines', 'journal_entries', 'ledger_accounts',
            'wallet_transactions', 'payments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
