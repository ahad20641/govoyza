<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task 1.5: users.type (which portal), the Owner/Operations/Finance staff
 * roles, and hashed one-time codes for email verification.
 */
return new class extends Migration
{
    /** role slug => user type, for backfilling existing users */
    private const TYPES = [
        'owner' => 'admin',
        'client' => 'customer',
        'agency' => 'agent_user',
        'vendor' => 'partner_user',
    ];

    public function up(): void
    {
        // Super Admin becomes Owner. Operations and Finance are created by govoyza:sync-permissions.
        DB::table('roles')->where('slug', 'super_admin')->update(['slug' => 'owner', 'name' => 'Owner']);

        Schema::table('users', function (Blueprint $table) {
            // customer | agent_user | partner_user | admin  (see App\Enums\UserType)
            $table->string('type', 20)->nullable()->after('role_id')->index();
        });

        foreach (self::TYPES as $slug => $type) {
            DB::table('users')
                ->whereIn('role_id', DB::table('roles')->where('slug', $slug)->select('id'))
                ->update(['type' => $type]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('type', 20)->nullable(false)->change();
        });

        Schema::create('one_time_passwords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 40);                   // App\Enums\OtpPurpose
            $table->char('code_hash', 64);                   // HMAC of the code; the code itself is never stored
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_time_passwords');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        DB::table('roles')->where('slug', 'owner')->update(['slug' => 'super_admin', 'name' => 'Super Admin']);
    }
};
