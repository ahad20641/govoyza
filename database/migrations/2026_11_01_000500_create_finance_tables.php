<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments (gateway-agnostic), receipts, B2B ledger and deposit requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 40)->unique();     // GoVoyza reference, e.g. PAY-...
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_draft_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete(); // set on success
            $table->string('gateway', 40);                         // driver key from config/payments.php
            $table->string('method', 40)->nullable();              // card, mobile_banking, ...
            $table->char('currency', 3)->default('BDT');
            $table->decimal('amount', 12, 2);
            $table->decimal('refunded_amount', 12, 2)->default(0);
            // initiated | pending | succeeded | failed | cancelled | refunded | partially_refunded
            $table->string('status', 25)->default('initiated')->index();
            $table->string('gateway_session_id', 191)->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                            // charge | refund | webhook | verify
            $table->string('gateway_transaction_id', 191)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status', 25);
            $table->json('payload')->nullable();                   // sanitized gateway response
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['payment_id', 'type', 'gateway_transaction_id'], 'payment_tx_idempotency');
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 40)->unique();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            $table->json('snapshot');                              // immutable copy of billed lines
            $table->timestamp('issued_at');
            $table->timestamps();
        });

        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->char('currency', 3)->default('BDT');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);  // cached; source of truth = transactions
            $table->timestamps();
        });

        Schema::create('deposit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('BDT');
            $table->string('method', 40);                          // bank_transfer, mobile_banking ...
            $table->string('transaction_reference', 100)->nullable();
            $table->string('proof_path')->nullable();              // private disk
            $table->text('note')->nullable();
            // pending | approved | rejected
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        // Append-only. Corrections are new 'adjustment' rows, never updates.
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('type', 25);                            // opening | deposit | service_charge | payment | adjustment | refund
            $table->string('description');
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('balance_after', 14, 2);
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deposit_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ledger_account_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('deposit_requests');
        Schema::dropIfExists('ledger_accounts');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
