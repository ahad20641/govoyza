<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A gateway payment for an order, or an agency wallet top-up. Written only by the gateway layer
 * (webhooks, idempotency key). Customer portal payments, admin accounting. Task 1.23 — Payment gateway layer.
 */
class Payment extends Model
{
    protected $fillable = ['order_id', 'agency_id', 'gateway', 'gateway_ref', 'amount_minor', 'currency', 'idempotency_key'];

    protected $hidden = ['raw'];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class.':amount_minor,currency',
            'status' => PaymentStatus::class,
            'raw' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
