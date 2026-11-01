<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint S1 — agencies get a pricing tier and an integer credit limit; users can belong to an
 * agency as sub-users (Phase 2) and carry a locale. Replaces the inherited decimal credit_limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('tier', 20)->default('silver')->after('status');   // App\Enums\AgencyTier
            $table->unsignedBigInteger('credit_limit_minor')->default(0)->after('status_reason');
            $table->char('currency', 3)->default('BDT')->after('credit_limit_minor');
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('credit_limit');
        });

        Schema::table('users', function (Blueprint $table) {
            // Sub-users of an agency (Phase 2). The agency owner is still agencies.user_id.
            $table->foreignId('agency_id')->nullable()->after('partner_id')->constrained()->nullOnDelete();
            $table->string('locale', 10)->default('en')->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropColumn('locale');
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->unsignedBigInteger('credit_limit')->default(0);
            $table->dropColumn(['tier', 'credit_limit_minor', 'currency']);
        });
    }
};
