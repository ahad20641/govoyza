<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One movement on an agency's prepaid wallet (signed amount + running balance). Append-only.
 * Agent portal wallet. Phase 2 — Wallet and credit.
 */
class WalletTransaction extends Model
{
    protected $fillable = ['type', 'amount_minor', 'balance_after_minor', 'currency', 'order_id', 'memo'];

    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'amount' => MoneyCast::class.':amount_minor,currency',
            'balance_after' => MoneyCast::class.':balance_after_minor,currency',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
