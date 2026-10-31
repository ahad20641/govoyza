<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applications and everything attached to them.
 *
 * Business rule §42.9: an application is created only after successful payment.
 * Before that, wizard state and uploaded files live on an application_draft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();                        // public identifier (non-sequential)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_price_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_step', 30)->default('passport');
            $table->json('data')->nullable();                      // wizard selections
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();             // GVZ-2026-000001
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Snapshot of what was selected (rules/prices may change later)
            $table->foreignId('visa_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_price_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('destination_country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignId('nationality_id')->constrained()->restrictOnDelete();
            $table->foreignId('visa_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('visa_type_id')->constrained()->restrictOnDelete();
            $table->string('service_level', 20);
            // Applicant (encrypted PII handled by model casts)
            $table->string('applicant_name');
            $table->text('passport_number');
            $table->string('passport_number_hash', 64)->index();   // public tracking verification
            $table->date('date_of_birth')->nullable();
            $table->string('applicant_email');
            $table->string('applicant_phone', 30)->nullable();
            // Handover (sticker visa only)
            $table->string('handover_method', 20)->default('none'); // none | courier_pickup | agency_dropoff
            $table->foreignId('pickup_location_id')->nullable()->constrained()->nullOnDelete();
            $table->text('pickup_address')->nullable();
            $table->foreignId('authorized_agency_id')->nullable()->constrained()->nullOnDelete();
            // Price snapshot
            $table->char('currency', 3)->default('BDT');
            $table->decimal('government_fee', 12, 2);
            $table->decimal('service_fee', 12, 2);
            $table->decimal('courier_fee', 12, 2)->default(0);
            $table->decimal('other_fee', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            // Status
            $table->string('status', 30)->default('payment_confirmed')->index(); // App\Enums\ApplicationStatus
            $table->string('payment_status', 20)->default('paid')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['agency_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            // Exactly one of these is set: draft (pre-payment) or application (post-payment)
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('application_draft_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('visa_document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 30)->default('private');
            $table->string('path');                                // random name, private disk
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64)->index();                   // duplicate-upload detection
            // pending | verified | rejected
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('scan_status', 20)->default('not_scanned'); // not_scanned | clean | infected | error
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('client_note')->nullable();               // visible to client
            $table->text('internal_note')->nullable();             // staff only
            $table->boolean('is_client_visible')->default(true);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'created_at']);
        });

        Schema::create('vendor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('vendor_price', 12, 2)->nullable();
            $table->string('status', 20)->default('active');      // active | replaced | cancelled
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('embassy_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submission_reference', 100)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('slip_disk', 30)->nullable();
            $table->string('slip_path')->nullable();
            $table->string('slip_original_name')->nullable();
            $table->boolean('is_client_visible')->default(true);
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 20);                       // pickup | delivery
            $table->string('courier_provider', 50)->nullable();    // matches a configured courier driver
            $table->string('tracking_reference', 100)->nullable();
            $table->string('status', 30)->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tracking');
        Schema::dropIfExists('embassy_submissions');
        Schema::dropIfExists('vendor_assignments');
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('application_drafts');
    }
};
