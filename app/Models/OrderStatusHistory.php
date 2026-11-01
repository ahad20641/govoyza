<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per order status change (from, to, actor, reason). Written only by the state machine;
 * the customer timeline and the admin order view read it. Task 1.18 — Order state machine.
 */
class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';

    protected $fillable = ['from_status', 'to_status', 'actor_id', 'reason'];

    protected function casts(): array
    {
        return [
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
