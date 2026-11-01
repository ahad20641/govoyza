<?php

namespace App\Enums;

enum KycStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Not submitted',
            self::Submitted => 'Submitted, under review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
