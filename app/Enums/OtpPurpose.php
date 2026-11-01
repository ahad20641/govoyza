<?php

namespace App\Enums;

/** What a one-time code proves. Phone OTP and login codes get their own cases later. */
enum OtpPurpose: string
{
    case EmailVerification = 'email_verification';
}
