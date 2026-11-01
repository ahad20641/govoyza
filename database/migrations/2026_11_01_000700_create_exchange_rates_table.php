<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-set exchange rates. 1 unit of `base` = `rate` units of `quote`.
 * Rows are never edited in place: a new rate is a new row with a later effective_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('base', 3);
            $table->char('quote', 3);
            $table->decimal('rate', 18, 8);                      // not money, so a decimal is fine
            $table->decimal('buffer_pct', 5, 2)->default(0);     // safety margin added on conversion
            $table->timestamp('effective_at');
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['base', 'quote', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
