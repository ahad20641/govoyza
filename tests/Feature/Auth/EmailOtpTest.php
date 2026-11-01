<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Models\OneTimePassword;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use App\Services\Auth\OneTimePasswords;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailOtpTest extends TestCase
{
    private function issue(User $user): string
    {
        return app(OneTimePasswords::class)->issue($user, OtpPurpose::EmailVerification);
    }

    private function wrong(string $code): string
    {
        return str_pad((string) (((int) $code + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public function test_code_is_six_digits_and_only_its_hash_is_stored(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->issue($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $otp = OneTimePassword::sole();
        $this->assertSame(64, strlen($otp->code_hash));
        $this->assertStringNotContainsString($code, $otp->code_hash);
        $this->assertArrayNotHasKey('code_hash', $otp->toArray());
    }

    public function test_wrong_code_is_rejected_and_counted(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->issue($user);

        $this->actingAs($user)->post(route('verification.verify'), ['code' => $this->wrong($code)])
            ->assertSessionHasErrors(['code' => OtpResult::Invalid->message()]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, OneTimePassword::sole()->attempts);
    }

    public function test_code_dies_after_five_wrong_guesses(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->issue($user);
        $otp = app(OneTimePasswords::class);

        foreach (range(1, OneTimePasswords::MAX_ATTEMPTS - 1) as $i) {
            $this->assertSame(OtpResult::Invalid, $otp->verify($user, OtpPurpose::EmailVerification, $this->wrong($code)));
        }
        $this->assertSame(OtpResult::TooManyAttempts, $otp->verify($user, OtpPurpose::EmailVerification, $this->wrong($code)));

        // Even the right code no longer works.
        $this->assertSame(OtpResult::TooManyAttempts, $otp->verify($user, OtpPurpose::EmailVerification, $code));
    }

    public function test_code_expires(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->issue($user);

        $this->travel(OneTimePasswords::TTL_MINUTES + 1)->minutes();

        $this->actingAs($user)->post(route('verification.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => OtpResult::Expired->message()]);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_code_works_once(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->issue($user);
        $otp = app(OneTimePasswords::class);

        $this->assertSame(OtpResult::Valid, $otp->verify($user, OtpPurpose::EmailVerification, $code));
        $this->assertSame(OtpResult::Expired, $otp->verify($user, OtpPurpose::EmailVerification, $code));
    }

    public function test_code_is_bound_to_its_user(): void
    {
        $alice = User::factory()->unverified()->create();
        $bob = User::factory()->unverified()->create();
        $code = $this->issue($alice);
        $this->issue($bob);

        $this->assertNotSame(OtpResult::Valid, app(OneTimePasswords::class)->verify($bob, OtpPurpose::EmailVerification, $code));
    }

    public function test_resend_replaces_the_old_code(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $old = $this->issue($user);

        $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('status');

        $new = null;
        Notification::assertSentTo($user, EmailVerificationCode::class, function ($n) use (&$new) {
            $new = $n->code;

            return true;
        });
        $this->assertSame(1, OneTimePassword::count());

        if ($old !== $new) {
            $this->post(route('verification.verify'), ['code' => $old])->assertSessionHasErrors('code');
        }
        $this->post(route('verification.verify'), ['code' => $new])->assertRedirect(route('account.dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_code_format_is_validated(): void
    {
        $user = User::factory()->unverified()->create();
        $this->issue($user);

        $this->actingAs($user)->post(route('verification.verify'), ['code' => '12ab'])->assertSessionHasErrors('code');
        $this->assertSame(0, OneTimePassword::sole()->attempts);
    }

    public function test_resend_and_verify_are_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $this->post(route('verification.send'))->assertRedirect();
        $this->post(route('verification.send'))->assertRedirect();
        $this->post(route('verification.send'))->assertTooManyRequests();

        foreach (range(1, 5) as $i) {
            $this->post(route('verification.verify'), ['code' => '000000']);
        }
        $this->post(route('verification.verify'), ['code' => '000000'])->assertTooManyRequests();
    }
}
