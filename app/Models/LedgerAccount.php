<?php

namespace App\Models;

use App\Enums\LedgerAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An account in the chart of accounts (planning/accounting-rules.md). Admin accounting.
 * Task 1.24 — Ledger.
 */
class LedgerAccount extends Model
{
    protected $fillable = ['code', 'name', 'type'];

    protected function casts(): array
    {
        return [
            'type' => LedgerAccountType::class,
        ];
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
