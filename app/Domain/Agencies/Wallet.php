<?php

namespace App\Domain\Agencies;

use App\Domain\NotBuiltYet;
use App\Models\Agency;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Support\Money\Money;

/**
 * An agency's prepaid wallet: balance, top-ups, instant deduction per order, credit limit with
 * auto-block when overdue. Agent portal and admin. Phase 2 — Wallet and credit.
 */
class Wallet
{
    public function balance(Agency $agency): Money
    {
        // TODO(Phase 2): latest wallet_transactions.balance_after_minor, per currency.
        return Money::ofMinor(0, $agency->currency);
    }

    public function charge(Agency $agency, Order $order): WalletTransaction
    {
        // TODO(Phase 2): lock, check balance + credit limit, append the transaction, post to the ledger.
        throw NotBuiltYet::task('2.x', 'Wallet payments');
    }
}
