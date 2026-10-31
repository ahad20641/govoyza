<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed reference data: countries, nationalities, districts,
 * visa categories, visa types, settings. Nothing here is hard-coded in code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->char('iso2', 2)->unique();
            $table->char('iso3', 3)->nullable()->unique();
            $table->string('flag', 16)->nullable();               // emoji or icon key
            $table->string('image_path')->nullable();             // destination hero image
            $table->text('description')->nullable();
            $table->boolean('is_destination')->default(false);    // offered as a visa destination
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        // A passport nationality. References a country so data is not duplicated,
        // but can be enabled/disabled independently of destinations.
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('demonym', 100);                       // e.g. "Bangladeshi"
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Service areas used for courier pickup & agency drop-off (spec §10).
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['country_id', 'name']);
        });

        Schema::create('visa_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                          // Tourist, Business ...
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('visa_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                          // E-Visa, Sticker Visa
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            // Behaviour flags instead of hard-coded "if sticker" checks:
            $table->boolean('requires_physical_passport')->default(false); // drives the Pickup/Drop-off step
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();                 // general, branding, mail, payment, courier
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');        // string, int, bool, json
            $table->boolean('is_encrypted')->default(false);      // stored with Crypt (for non-env secrets)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('visa_types');
        Schema::dropIfExists('visa_categories');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('nationalities');
        Schema::dropIfExists('countries');
    }
};
