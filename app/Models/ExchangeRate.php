<?php

namespace App\Models;

use App\Enums\Currency;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = ['base', 'quote', 'rate', 'buffer_pct', 'effective_at', 'set_by'];

    protected function casts(): array
    {
        return [
            'base' => Currency::class,
            'quote' => Currency::class,
            'rate' => 'decimal:8',
            'buffer_pct' => 'decimal:2',
            'effective_at' => 'datetime',
        ];
    }

    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    /** Latest rate for the pair in effect at the given moment. */
    public function scopeInEffect(Builder $query, Currency $base, Currency $quote, DateTimeInterface $at): void
    {
        $query->where('base', $base->value)
            ->where('quote', $quote->value)
            ->where('effective_at', '<=', $at)
            ->latest('effective_at');
    }
}
