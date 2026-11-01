<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A batch of amounts owed to one partner for a period (draft → approved → paid).
 * Admin accounting and partner earnings. Task 1.27 (payables list), Phase 2 (weekly payout batch).
 */
class Payout extends Model
{
    protected $fillable = ['period', 'total_minor', 'currency'];

    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'total' => MoneyCast::class.':total_minor,currency',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
