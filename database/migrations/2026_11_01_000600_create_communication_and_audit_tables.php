<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications (Laravel standard table + admin templates), secure chat,
 * saved reports, audit logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Laravel's database notification channel (in-app notification centre).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 60);                       // application.submitted, payment.succeeded ...
            $table->string('channel', 20);                         // mail | database | push | sms
            $table->string('subject')->nullable();
            $table->text('body');                                  // supports {{ placeholders }}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['event_key', 'channel']);
        });

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 30);                            // client_agency | agency_vendor | support
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        // Access control for chat: only participants can read/post.
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('display_name', 100);                   // shown instead of personal contact details
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['chat_conversation_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete(); // NULL = system
            $table->string('type', 20)->default('text');           // text | attachment | image | system
            $table->text('body')->nullable();
            $table->string('attachment_disk', 30)->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamps();
            $table->index(['chat_conversation_id', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 50);                            // applications, revenue, vendor_usage ...
            $table->json('filters')->nullable();
            $table->string('format', 10);                          // csv | pdf
            $table->string('status', 20)->default('queued');       // queued | ready | failed
            $table->string('file_path')->nullable();               // private disk
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index();                // auth.login, agency.approved ...
            $table->nullableMorphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_participants');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notifications');
    }
};
