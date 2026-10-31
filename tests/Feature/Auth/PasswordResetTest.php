<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_full_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewSecret123', $user->fresh()->password));
    }

    public function test_unknown_email_gets_same_response(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status', 'If an account exists for that email, we have sent a password reset link.');
        Notification::assertNothingSent();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'bogus', 'email' => $user->email,
            'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
