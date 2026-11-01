<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A balanced double-entry journal entry for one money event. Immutable: corrections are reversing
 * entries (CLAUDE.md rule 4). Written only by App\Domain\Ledger\LedgerPoster. Task 1.24 — Ledger.
 */
class JournalEntry extends Model
{
    protected $fillable = ['posted_at', 'event', 'memo'];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
        ];
    }

    // TODO(1.24): block update/delete in booted() so entries stay immutable.

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_entry_id');
    }
}
