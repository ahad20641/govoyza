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
        foreach (['/client', '/agency', '/dashboard'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_each_role_is_confined_to_its_portal(): void
    {
        $portals = ['client' => '/client', 'agency' => '/agency'];

        foreach ($portals as $role => $own) {
            $user = User::factory()->role(RoleSlug::from($role))->create();

            foreach ($portals as $otherRole => $path) {
                $response = $this->actingAs($user)->get($path);
                $otherRole === $role ? $response->assertOk() : $response->assertForbidden();
            }
        }
    }

    public function test_all_staff_roles_use_the_admin_portal_only(): void
    {
        foreach (RoleSlug::staff() as $role) {
            $staff = User::factory()->role($role)->create();

            // The old /admin shell is gone; staff work on the admin domain (AdminConsoleTest).
            $this->actingAs($staff)->get('/admin')->assertNotFound();
            $this->actingAs($staff)->get('/dashboard')->assertRedirect(route('filament.admin.pages.dashboard'));
            foreach (['/client', '/agency'] as $path) {
                $this->actingAs($staff)->get($path)->assertForbidden();
            }
        }
    }

    public function test_partner_users_use_the_partner_portal_only(): void
    {
        $partner = User::factory()->partner()->create();

        $this->actingAs($partner)->get('/vendor')->assertNotFound(); // old shell is gone
        $this->actingAs($partner)->get('/dashboard')->assertRedirect(route('filament.partner.pages.dashboard'));
        foreach (['/client', '/agency'] as $path) {
            $this->actingAs($partner)->get($path)->assertForbidden();
        }
    }

    public function test_permissions_come_from_role(): void
    {
        $client = User::factory()->create();
        $agency = User::factory()->agency()->create();
        $admin = User::factory()->owner()->create();

        $this->assertTrue(Gate::forUser($client)->allows('applications.create'));
        $this->assertFalse(Gate::forUser($client)->allows('b2b.view_ledger'));
        $this->assertFalse(Gate::forUser($client)->allows('admin.access'));

        $this->assertTrue(Gate::forUser($agency)->allows('b2b.view_ledger'));
        $this->assertFalse(Gate::forUser($agency)->allows('agencies.manage'));

        // Owner has full access, including permissions added later.
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
