<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parties: B2C clients, B2B agencies (+ their verification documents and
 * drop-off branches), vendors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('nationality_id')->nullable()->constrained()->nullOnDelete();
            $table->text('passport_number')->nullable();          // encrypted at rest (model cast)
            $table->string('passport_number_hash', 64)->nullable()->index(); // for lookup/verification without decrypting
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // primary account owner
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('trade_license_number', 100);
            $table->string('business_email');
            $table->string('business_phone', 30);
            $table->text('address');
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->string('website')->nullable();
            // pending | approved | rejected | suspended  (App\Enums\AgencyStatus)
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('status_reason')->nullable();             // rejection / suspension reason
            // Admin-set limits (spec §22, §31)
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('max_markup_percent', 5, 2)->nullable(); // cap on agency pricing over base price
            $table->boolean('can_set_prices')->default(true);
            $table->boolean('is_authorized_dropoff')->default(false); // may receive client passports
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('agency_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);                   // trade_license, owner_nid, tin ...
            $table->string('disk', 30)->default('private');
            $table->string('path');                                // random, non-guessable
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            // pending | verified | rejected
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // Physical drop-off points shown to clients (spec §10). Public-safe data only.
        Schema::create('authorized_agencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->string('branch_name');
            $table->text('address');
            $table->json('office_hours')->nullable();              // {"sun":"10:00-18:00",...}
            $table->json('services_offered')->nullable();          // visa_type / category ids or labels
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index(['district_id', 'is_active']);
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete(); // optional vendor login
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('address')->nullable();
            // active | inactive
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_approved')->default(false);
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('internal_notes')->nullable();            // never exposed to clients
            $table->boolean('is_demo')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('authorized_agencies');
        Schema::dropIfExists('agency_documents');
        Schema::dropIfExists('agencies');
        Schema::dropIfExists('clients');
    }
};
