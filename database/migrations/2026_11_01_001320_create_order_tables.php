<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint S1 — orders and everything hanging off them (planning/data-model.md):
 * requirements, orders, status history, applicants, saved travellers, documents,
 * service jobs and their updates. Money is *_minor BIGINT + currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ==================== Catalogue: checklist per product (task 1.11) ====================
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('doc_type', 50);                 // passport, photo, bank_statement, …
            $table->boolean('is_required')->default(true);
            $table->json('rules')->nullable();              // formats, max_mb, passport_valid_months
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ==================== Orders (tasks 1.17, 1.18) ====================
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            // Buyer: the user who placed it, plus the agency for B2B. Real foreign keys
            // instead of a buyer_type/buyer_id morph (decision 2026-11-01, "Skeleton first").
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('channel', 10);                  // b2c | b2b
            $table->string('tier', 20)->default('retail');  // retail or an App\Enums\AgencyTier
            $table->string('status', 30)->default('placed')->index();
            $table->string('decision', 20)->nullable();     // approved | rejected | additional_docs
            $table->json('price_snapshot')->nullable();     // locked at placed; never changes after
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->char('currency', 3)->default('BDT');
            $table->date('travel_date')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['agency_id', 'status']);
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // ==================== Travellers ====================
        Schema::create('saved_travellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('given_names');
            $table->string('surname');
            $table->text('passport_number')->nullable();    // encrypted cast
            $table->char('nationality_iso2', 2)->nullable();
            $table->text('date_of_birth')->nullable();      // encrypted cast
            $table->date('passport_expiry')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saved_traveller_id')->nullable()->constrained()->nullOnDelete();
            $table->string('given_names');
            $table->string('surname');
            $table->text('passport_number')->nullable();    // encrypted cast
            $table->char('nationality_iso2', 2)->nullable();
            $table->text('date_of_birth')->nullable();      // encrypted cast
            $table->date('passport_expiry')->nullable();
            $table->timestamps();
        });

        // ==================== Documents (task 1.19) ====================
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 30)->default('private');  // never a public disk
            $table->string('path');                          // random, non-guessable
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('check_status', 20)->default('pending'); // pending | ok | flagged
            $table->text('flag_reason')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ==================== Jobs for partners (tasks 1.20, 1.21) ====================
        // `jobs` is the Laravel queue table, hence service_jobs.
        Schema::create('service_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_offer_id')->constrained()->restrictOnDelete();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('assigned')->index();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('closed_at')->nullable();      // partner loses file access from here
            $table->unsignedBigInteger('cost_snapshot_minor');
            $table->char('currency', 3);
            $table->timestamps();

            $table->index(['partner_id', 'status']);
        });

        Schema::create('service_job_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_job_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->text('note')->nullable();
            $table->timestamp('appointment_at')->nullable();
            $table->foreignId('result_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['service_job_updates', 'service_jobs', 'documents', 'applicants', 'saved_travellers',
            'order_status_history', 'orders', 'requirements'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
