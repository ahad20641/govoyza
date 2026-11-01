<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleSlug;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Notification;
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

    public function test_client_can_register_and_must_verify_email_with_a_code(): void
    {
        Notification::fake();

        $this->post('/register/client', $this->payload())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'rahim@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(RoleSlug::Client));
        $this->assertSame(UserType::Customer, $user->type);
        $this->assertNotNull($user->client);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(AuditLog::where('action', 'auth.registered_client')->exists());

        $code = null;
        Notification::assertSentTo($user, EmailVerificationCode::class, function ($n) use (&$code) {
            $code = $n->code;

            return true;
        });

        // Unverified users cannot reach the dashboard.
        $this->get('/client')->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('6-digit code');

        $this->post(route('verification.verify'), ['code' => $code])->assertRedirect(route('client.dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/client')->assertOk();
    }

    public function test_role_and_type_cannot_be_injected_through_registration(): void
    {
        $admin = Role::where('slug', 'owner')->first();

        $this->post('/register/client', $this->payload(['role_id' => $admin->id, 'type' => 'admin', 'status' => 'active']));

        $user = User::firstWhere('email', 'rahim@example.com');
        $this->assertTrue($user->hasRole(RoleSlug::Client));
        $this->assertSame(UserType::Customer, $user->type);
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
}
