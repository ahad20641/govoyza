<?php

namespace App\Enums;

enum PartnerStatus: string
{
    case Pending = 'pending';      // onboarding, waiting for admin approval
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending approval',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
    }
}
