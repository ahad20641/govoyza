<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint S1 — order messages, the notification log and the small CMS (pages, posts, coupons).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ==================== Messages and notifications (tasks 1.22, 1.28) ====================
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete(); // null = system
            // customer | partner | internal. Customers and partners never share a thread:
            // admin relays partner messages (CLAUDE.md rules 1 and 2).
            $table->string('audience', 20);
            $table->text('body');
            $table->timestamps();

            $table->index(['order_id', 'audience']);
        });

        Schema::create('notifications_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);                   // email | sms | whatsapp
            $table->string('template', 100);
            $table->string('status', 20)->default('queued');
            $table->timestamps();
        });

        // ==================== CMS (task 1.15) ====================
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('discount_type', 20);             // fixed | percent (App\Enums\MarkupType)
            // fixed: minor units of `currency`; percent: basis points (1250 = 12.5%).
            $table->unsignedBigInteger('discount_value');
            $table->char('currency', 3)->nullable();
            $table->string('channel', 10)->default('b2c');   // B2C-only by default (blueprint)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['coupons', 'posts', 'pages', 'notifications_log', 'messages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
