<?php

namespace App\Enums;

/**
 * Partner payout batch state. Admin accounting and partner earnings. Task 1.27 / Phase 2 — Payouts.
 */
enum PayoutStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Paid = 'paid';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
