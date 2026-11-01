<?php

namespace App\Services\Auth;

use App\Enums\OtpPurpose;
use App\Enums\OtpResult;
use App\Models\OneTimePassword;
use App\Models\User;
use App\Support\Hashing;
use Illuminate\Support\Facades\DB;

/**
 * Short-lived numeric codes sent by email (phone later). Only a keyed hash
 * is stored, each code works once, and a code dies after MAX_ATTEMPTS wrong
 * guesses. Request rate limits on the routes sit on top of this.
 */
class OneTimePasswords
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Issues a new code and cancels any earlier unused code for the same purpose. */
    public function issue(User $user, OtpPurpose $purpose): string
    {
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $purpose, $code) {
            $user->oneTimePasswords()->where('purpose', $purpose)->whereNull('consumed_at')->delete();

            $otp = new OneTimePassword;
            $otp->user_id = $user->id;
            $otp->purpose = $purpose;
            $otp->code_hash = Hashing::otp($code, $user->id, $purpose->value);
            $otp->expires_at = now()->addMinutes(self::TTL_MINUTES);
            $otp->save();
        });

        return $code;
    }

    public function verify(User $user, OtpPurpose $purpose, string $code): OtpResult
    {
        return DB::transaction(function () use ($user, $purpose, $code) {
            $otp = $user->oneTimePasswords()
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp || $otp->expires_at->isPast()) {
                return OtpResult::Expired;
            }

            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                return OtpResult::TooManyAttempts;
            }

            $otp->increment('attempts');

            if (! hash_equals($otp->code_hash, Hashing::otp($code, $user->id, $purpose->value))) {
                return $otp->attempts >= self::MAX_ATTEMPTS ? OtpResult::TooManyAttempts : OtpResult::Invalid;
            }

            $otp->consumed_at = now();
            $otp->save();

            return OtpResult::Valid;
        });
    }
}
