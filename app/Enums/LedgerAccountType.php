<?php

namespace App\Enums;

/**
 * Chart of accounts class (planning/accounting-rules.md). Admin accounting. Task 1.24 — Ledger.
 */
enum LedgerAccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
