<?php

namespace App\Enums;

/**
 * Gateway payment state. Task 1.23 — Payment gateway layer.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
