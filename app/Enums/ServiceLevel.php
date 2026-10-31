<?php

namespace App\Enums;

enum ServiceLevel: string
{
    case Normal = 'normal';
    case Urgent = 'urgent';
    case SuperUrgent = 'super_urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Urgent => 'Urgent',
            self::SuperUrgent => 'Super Urgent',
        };
    }
}
