<?php

namespace App\Enums;

/**
 * Who bought: a B2C customer or a B2B agency. Public site and agent portal. Task 1.17 — Order placement.
 */
enum OrderChannel: string
{
    case B2c = 'b2c';
    case B2b = 'b2b';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
