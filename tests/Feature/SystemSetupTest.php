<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use LogicException;
use Tests\TestCase;

class SystemSetupTest extends TestCase
{
    public function test_seeder_creates_roles_and_no_users(): void
    {
        $this->assertSame(count(RoleSlug::cases()), Role::count());
        $this->assertGreaterThan(20, Permission::count());
        $this->assertSame(0, User::count());
    }

    public function test_permission_sync_is_idempotent(): void
    {
        $count = Permission::count();
        $this->artisan('govoyza:sync-permissions')->assertSuccessful();
        $this->artisan('govoyza:sync-permissions')->assertSuccessful();
        $this->assertSame($count, Permission::count());
    }

    public function test_create_admin_command_enforces_strong_password(): void
    {
        $this->artisan('govoyza:create-admin', ['--name' => 'Ops', '--email' => 'ops@govoyza.test', '--password' => 'weak'])
            ->assertFailed();
        $this->assertSame(0, User::count());

        $this->artisan('govoyza:create-admin', ['--name' => 'Ops', '--email' => 'ops@govoyza.test', '--password' => 'Str0ng!Passw0rd'])
            ->assertSuccessful();

        $admin = User::firstWhere('email', 'ops@govoyza.test');
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue(AuditLog::where('action', 'admin.created_via_cli')->exists());
    }

    public function test_audit_logs_are_immutable_and_mask_secrets(): void
    {
        $log = app(\App\Services\Audit\AuditLogger::class)->log('test.action', new: ['password' => 'x', 'name' => 'ok']);

        $this->assertSame('***', $log->new_values['password']);
        $this->assertSame('ok', $log->new_values['name']);

        $this->expectException(LogicException::class);
        $log->update(['action' => 'tampered']);
    }
}
