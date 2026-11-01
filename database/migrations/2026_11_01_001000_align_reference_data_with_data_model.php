<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 1.8: make reference data match planning/data-model.md.
 *
 * The inherited schema called the visa *purpose* (tourist, business…) a
 * "category" and the delivery *format* (e-visa, sticker) a "type". The data
 * model, and products from 1.9, use `visa_types` for the purpose. So:
 *   visa_types      → visa_formats   (e-visa / sticker)
 *   visa_categories → visa_types     (the 7 purposes)
 * and the legacy referencing columns follow. Countries get slug, region,
 * continent and SEO fields.
 */
return new class extends Migration
{
    /** Legacy tables that reference both (unused by app code; rebuilt in 1.9/1.17). */
    private const REFERENCING = ['visa_rules', 'applications'];

    public function up(): void
    {
        foreach (self::REFERENCING as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('visa_type_id', 'visa_format_id');
            });
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('visa_category_id', 'visa_type_id');
            });
        }

        Schema::rename('visa_types', 'visa_formats');
        Schema::rename('visa_categories', 'visa_types');

        Schema::table('countries', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->unique()->after('iso3');  // /visa/{slug}
            // Visa-relevant grouping: schengen | gcc | null (App\Enums\CountryRegion)
            $table->string('region', 20)->nullable()->index()->after('slug');
            // africa | asia | europe | north_america | south_america | oceania | antarctica
            $table->string('continent', 20)->nullable()->index()->after('region');
            $table->string('seo_title')->nullable()->after('description');
            $table->string('seo_description', 320)->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['region']);
            $table->dropIndex(['continent']);
            $table->dropColumn(['slug', 'region', 'continent', 'seo_title', 'seo_description']);
        });

        Schema::rename('visa_types', 'visa_categories');
        Schema::rename('visa_formats', 'visa_types');

        foreach (self::REFERENCING as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('visa_type_id', 'visa_category_id');
            });
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('visa_format_id', 'visa_type_id');
            });
        }
    }
};
