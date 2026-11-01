<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task 1.7: partners (suppliers) per planning/data-model.md, their KYC documents
 * and invite links. The inherited `vendors` tables stay until the tasks that
 * rebuild the catalogue, applications and finance tables replace them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The supplier role is called partner from here on.
        DB::table('roles')->where('slug', 'vendor')->update(['slug' => 'partner', 'name' => 'Partner']);

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('contact_phone', 30)->nullable();
            // pending | active | suspended  (App\Enums\PartnerStatus)
            $table->string('status', 20)->default('pending')->index();
            // not_submitted | submitted | approved | rejected  (App\Enums\KycStatus)
            $table->string('kyc_status', 20)->default('not_submitted');
            $table->text('bank_details')->nullable();               // encrypted JSON (model cast)
            $table->unsignedTinyInteger('sla_score')->nullable();   // 0-100, filled from Phase 3
            // Click-to-sign record of the partner agreement
            $table->string('agreement_version', 40)->nullable();
            $table->char('agreement_text_sha256', 64)->nullable();  // proves which text was signed
            $table->string('agreement_signed_name')->nullable();
            $table->timestamp('agreement_signed_at')->nullable();
            $table->string('agreement_ip', 45)->nullable();
            $table->foreignId('agreement_signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('status_reason')->nullable();               // suspension reason
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });

        Schema::create('partner_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);                     // App\Enums\PartnerDocumentType
            $table->string('disk', 30)->default('private');
            $table->string('path');                                  // random, non-guessable
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('partner_invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('legal_name')->nullable();                // suggested company name
            $table->char('token_hash', 64)->unique();                // sha256 of the link token; the token itself is never stored
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_invitations');
        Schema::dropIfExists('partner_documents');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
        });
        Schema::dropIfExists('partners');
        DB::table('roles')->where('slug', 'partner')->update(['slug' => 'vendor', 'name' => 'Vendor']);
    }
};
