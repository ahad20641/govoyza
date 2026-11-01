<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One debit or credit line of a journal entry, optionally tagged with the partner or agency it
 * concerns (for payables and wallet statements). Task 1.24 — Ledger.
 */
class JournalLine extends Model
{
    protected $fillable = ['ledger_account_id', 'debit_minor', 'credit_minor', 'currency', 'partner_id', 'agency_id'];

    protected function casts(): array
    {
        return [
            'debit' => MoneyCast::class.':debit_minor,currency',
            'credit' => MoneyCast::class.':credit_minor,currency',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
