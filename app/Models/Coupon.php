<?php

namespace App\Models;

use App\Enums\MarkupType;
use App\Enums\OrderChannel;
use Illuminate\Database\Eloquent\Model;

/**
 * A promo code. B2C-only by default (blueprint). Applied by the pricing engine, never by hand.
 * Admin CMS. Task 1.12 — Pricing engine (application), 1.15 (admin screen).
 *
 * discount_value: minor units for `fixed`, basis points for `percent` (1250 = 12.5%).
 */
class Coupon extends Model
{
    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'currency', 'channel', 'starts_at', 'ends_at', 'max_uses', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => MarkupType::class,
            'channel' => OrderChannel::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
