<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Record of every email/SMS/WhatsApp sent, for support and delivery checks.
 * Task 1.28 — Email notifications.
 */
class NotificationLog extends Model
{
    protected $table = 'notifications_log';

    protected $fillable = ['user_id', 'order_id', 'channel', 'template', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
