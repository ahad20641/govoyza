<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_users_land_on_their_role_dashboard(): void
    {
        $cases = [
            [User::factory()->create(), '/account'],
            [User::factory()->agency()->create(), '/agent'],
        ];

        foreach ($cases as [$user, $path]) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(url($path));
            $this->get($path)->assertOk();
            $this->post('/logout');
        }

        $this->assertNotNull(User::first()->last_login_at);
        $this->assertSame(2, AuditLog::where('action', 'auth.login')->count());
    }

    public function test_staff_and_partners_are_sent_to_their_panel_to_sign_in(): void
    {
        $cases = [
            [User::factory()->owner()->create(), 'filament.admin.auth.login'],
            [User::factory()->partner()->create(), 'filament.partner.auth.login'],
        ];

        foreach ($cases as [$user, $login]) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route($login));

            // Not signed in on the public site: each panel has its own session and requires 2FA.
            $this->assertGuest();
        }
    }

    public function test_wrong_password_fails_and_is_audited(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'auth.login_failed')->exists());
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // Even the correct password is refused while locked out.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    public function test_suspended_and_rejected_users_cannot_log_in(): void
    {
        foreach ([UserStatus::Suspended, UserStatus::Rejected] as $status) {
            $user = User::factory()->status($status)->create();

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_user_suspended_mid_session_is_logged_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/account')->assertOk();

        $user->status = UserStatus::Suspended;
        $user->save();

        $this->actingAs($user->fresh())->get('/account')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_session_is_regenerated_on_login(): void
    {
        $user = User::factory()->create();
        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_csrf_middleware_protects_login(): void
    {
        $middleware = app(Kernel::class)->getMiddlewareGroups()['web'];

        $csrf = class_exists(PreventRequestForgery::class) ? PreventRequestForgery::class : ValidateCsrfToken::class;
        $this->assertContains($csrf, $middleware);
    }
}
