<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleSlug;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientRegistrationTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rahim Uddin',
            'email' => 'Rahim@Example.com',
            'phone' => '+880 1711 000000',
            'password' => 'Secret123pass',
            'password_confirmation' => 'Secret123pass',
            'terms' => '1',
        ], $overrides);
    }

    public function test_client_can_register_and_must_verify_email(): void
    {
        Notification::fake();

        $this->post('/register/client', $this->payload())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'rahim@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(RoleSlug::Client));
        $this->assertNotNull($user->client);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertTrue(AuditLog::where('action', 'auth.registered_client')->exists());

        // Unverified users cannot reach the dashboard.
        $this->get('/client')->assertRedirect(route('verification.notice'));
    }

    public function test_role_cannot_be_injected_through_registration(): void
    {
        $admin = Role::where('slug', 'super_admin')->first();

        $this->post('/register/client', $this->payload(['role_id' => $admin->id, 'status' => 'active']));

        $this->assertTrue(User::firstWhere('email', 'rahim@example.com')->hasRole(RoleSlug::Client));
    }

    public function test_validation_rules(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register/client', $this->payload([
            'email' => 'taken@example.com', 'password_confirmation' => 'nope', 'terms' => null,
        ]))->assertSessionHasErrors(['email', 'password', 'terms']);

        $this->post('/register/client', $this->payload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_email_verification_link_verifies_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('client.dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_tampered_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get("/email/verify/{$user->id}/".sha1($user->email))->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
