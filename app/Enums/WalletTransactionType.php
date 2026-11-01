<?php

namespace App\Enums;

/**
 * Agent wallet movement. Agent portal. Phase 2 — Wallet.
 */
enum WalletTransactionType: string
{
    case Topup = 'topup';
    case Order = 'order';
    case Refund = 'refund';
    case Adjust = 'adjust';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
