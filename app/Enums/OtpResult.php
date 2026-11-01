<?php

namespace App\Enums;

enum OtpResult: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Expired = 'expired';          // also: no code issued, or already used
    case TooManyAttempts = 'too_many_attempts';

    public function message(): string
    {
        return match ($this) {
            self::Valid => 'Code accepted.',
            self::Invalid => 'That code is not correct. Check the email and try again.',
            self::Expired => 'That code has expired or was already used. Request a new one.',
            self::TooManyAttempts => 'Too many wrong codes. Request a new one.',
        };
    }
}
