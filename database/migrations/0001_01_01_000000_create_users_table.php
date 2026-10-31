<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity & access: roles, permissions, users, sessions, password resets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();          // super_admin, client, agency, vendor
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(true);   // system roles cannot be deleted
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();          // e.g. applications.view_any
            $table->string('name', 150);
            $table->string('group', 50)->index();           // applications, agencies, pricing...
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // active | pending | suspended | rejected  (see App\Enums\UserStatus)
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            // Prepared for future OTP / 2FA (spec §39)
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
