<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Pending = 'pending';      // e.g. agency awaiting admin approval
    case Suspended = 'suspended';
    case Rejected = 'rejected';

    public function canLogIn(): bool
    {
        return in_array($this, [self::Active, self::Pending], true);
    }
}
