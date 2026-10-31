<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visa rule engine, document requirements, services & pricing, courier
 * coverage, and agency/vendor service offerings.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rule: destination + (nationality | any) + category => visa type (spec §28).
        Schema::create('visa_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('destination_country_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('nationality_id')->nullable()->constrained()->cascadeOnDelete(); // NULL = applies to all nationalities
            $table->foreignId('visa_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_type_id')->constrained()->restrictOnDelete();
            $table->text('client_notes')->nullable();              // shown to applicants
            $table->text('internal_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            // NOTE: uniqueness of (destination, nationality, category) is enforced in the
            // VisaRule service layer because NULL nationality breaks composite unique indexes.
            $table->index(['destination_country_id', 'visa_category_id', 'is_active'], 'visa_rules_lookup_idx');
        });

        // Document requirements per rule (spec §29). Drives checklist + upload page.
        Schema::create('visa_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_rule_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('document_type', 50)->nullable();       // passport, photo, bank_statement ...
            $table->text('description')->nullable();               // "why we need this" (UX §43)
            $table->boolean('is_required')->default(true);
            $table->json('allowed_extensions');                    // ["jpg","jpeg","png","pdf"]
            $table->unsignedInteger('max_size_kb')->default(5120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['visa_rule_id', 'is_active']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_rule_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_available')->default(true);        // temporarily unavailable
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        // One row per service level (normal/urgent/super_urgent). Total is computed
        // by the PricingService, never stored as a free-typed number.
        Schema::create('service_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('service_level', 20);                   // App\Enums\ServiceLevel
            $table->unsignedSmallInteger('processing_days_min');
            $table->unsignedSmallInteger('processing_days_max');
            $table->char('currency', 3)->default('BDT');
            $table->decimal('government_fee', 12, 2)->default(0);
            $table->decimal('service_fee', 12, 2)->default(0);
            $table->decimal('courier_fee', 12, 2)->default(0);
            $table->decimal('other_fee', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            $table->unique(['service_id', 'service_level']);
        });

        // Courier pickup coverage (spec §10: "show only when courier is available").
        Schema::create('pickup_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->cascadeOnDelete();
            $table->string('area_name')->nullable();               // NULL = whole district
            $table->boolean('courier_available')->default(true);
            $table->decimal('courier_fee', 12, 2)->nullable();     // overrides service_prices.courier_fee
            $table->unsignedSmallInteger('pickup_lead_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['district_id', 'is_active']);
        });

        Schema::create('agency_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('normal_price', 12, 2)->nullable();
            $table->decimal('urgent_price', 12, 2)->nullable();
            $table->decimal('super_urgent_price', 12, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['agency_id', 'service_id']);
        });

        Schema::create('vendor_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('normal_price', 12, 2)->nullable();
            $table->decimal('urgent_price', 12, 2)->nullable();
            $table->decimal('super_urgent_price', 12, 2)->nullable();
            $table->unsignedSmallInteger('processing_days')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->unique(['vendor_id', 'service_id']);
        });

        // Which vendors an agency may use (spec §31 "Assign vendors").
        Schema::create('agency_vendor', function (Blueprint $table) {
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['agency_id', 'vendor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_vendor');
        Schema::dropIfExists('vendor_services');
        Schema::dropIfExists('agency_services');
        Schema::dropIfExists('pickup_locations');
        Schema::dropIfExists('service_prices');
        Schema::dropIfExists('services');
        Schema::dropIfExists('visa_documents');
        Schema::dropIfExists('visa_rules');
    }
};
