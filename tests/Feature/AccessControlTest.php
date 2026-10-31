<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/client', '/agency', '/vendor', '/admin', '/dashboard'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_each_role_is_confined_to_its_portal(): void
    {
        $portals = ['client' => '/client', 'agency' => '/agency', 'vendor' => '/vendor', 'super_admin' => '/admin'];

        foreach ($portals as $role => $own) {
            $user = User::factory()->role(RoleSlug::from($role))->create();

            foreach ($portals as $otherRole => $path) {
                $response = $this->actingAs($user)->get($path);
                $otherRole === $role ? $response->assertOk() : $response->assertForbidden();
            }
        }
    }

    public function test_permissions_come_from_role(): void
    {
        $client = User::factory()->create();
        $agency = User::factory()->agency()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->assertTrue(Gate::forUser($client)->allows('applications.create'));
        $this->assertFalse(Gate::forUser($client)->allows('b2b.view_ledger'));
        $this->assertFalse(Gate::forUser($client)->allows('admin.access'));

        $this->assertTrue(Gate::forUser($agency)->allows('b2b.view_ledger'));
        $this->assertFalse(Gate::forUser($agency)->allows('agencies.manage'));

        // Super Admin has full access, including permissions added later.
        $this->assertTrue(Gate::forUser($admin)->allows('agencies.manage'));
        $this->assertTrue(Gate::forUser($admin)->allows('anything.new'));
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
