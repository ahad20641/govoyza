<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('visa_type_id')->constrained()->restrictOnDelete();
            $table->char('nationality_iso2', 2)->nullable();
            $table->string('public_name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('processing_days_min')->nullable();
            $table->unsignedSmallInteger('processing_days_max')->nullable();
            $table->string('validity', 100)->nullable();
            $table->string('b2c_markup_type', 20)->default('percent');
            $table->unsignedBigInteger('b2c_markup_value')->default(0);
            $table->string('b2b_markup_type', 20)->default('percent');
            $table->unsignedBigInteger('b2b_markup_value')->default(0);
            $table->unsignedBigInteger('embassy_fee_minor')->default(0);
            $table->char('currency', 3)->default('BDT');
            $table->string('status', 20)->default('draft')->index();
            $table->string('slug')->nullable()->unique();
            $table->timestamps();

            $table->foreign('nationality_iso2')->references('iso2')->on('countries')->restrictOnDelete();
            $table->index(['country_id', 'visa_type_id', 'nationality_iso2']);
        });

        Schema::create('supplier_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('cost_minor');
            $table->char('currency', 3);
            $table->unsignedSmallInteger('processing_days');
            $table->unsignedInteger('capacity_per_week')->nullable();
            $table->string('priority', 20)->default('other');
            $table->string('status', 20)->default('draft')->index();
            $table->json('inclusions')->nullable();
            $table->text('refund_terms')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'partner_id']);
            $table->index(['partner_id', 'status']);
        });

        Schema::create('offer_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_offer_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_offer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_revisions');
        Schema::dropIfExists('supplier_offers');
        Schema::dropIfExists('products');
    }
};
