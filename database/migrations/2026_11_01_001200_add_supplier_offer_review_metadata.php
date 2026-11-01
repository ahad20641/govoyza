<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_offers', function (Blueprint $table) {
            $table->foreignId('reviewer_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewer_id');
            $table->text('review_comment')->nullable()->after('reviewed_at');
        });

    }

    public function down(): void
    {
        Schema::table('supplier_offers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropColumn(['reviewed_at', 'review_comment']);
        });
    }
};
