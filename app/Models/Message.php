<?php

namespace App\Models;

use App\Enums\MessageAudience;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message on an order thread. The audience keeps customers and partners apart: admin relays
 * between them, so neither ever sees the other (CLAUDE.md rules 1 and 2). Task 1.22 — Customer portal.
 */
class Message extends Model
{
    protected $fillable = ['author_id', 'audience', 'body'];

    protected function casts(): array
    {
        return [
            'audience' => MessageAudience::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
