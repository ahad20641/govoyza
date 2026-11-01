<?php

namespace App\Domain\Ledger;

use App\Domain\NotBuiltYet;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes one balanced double-entry journal entry per money event (CLAUDE.md rule 4), inside a DB
 * transaction, and reversing entries for corrections. Called by listeners on order and payment events,
 * never by controllers. Admin accounting. Task 1.24 — Ledger (planning/accounting-rules.md).
 */
class LedgerPoster
{
    /**
     * @param  list<array{account: string, debit_minor?: int, credit_minor?: int, currency: string}>  $lines
     */
    public function post(string $event, Model $reference, array $lines, ?string $memo = null): JournalEntry
    {
        // TODO(1.24): balanced-per-currency guard, account lookup by code, immutable entry + lines.
        throw NotBuiltYet::task('1.24', 'Ledger posting');
    }

    public function reverse(JournalEntry $entry, string $reason): JournalEntry
    {
        // TODO(1.24)
        throw NotBuiltYet::task('1.24', 'Ledger reversals');
    }
}
