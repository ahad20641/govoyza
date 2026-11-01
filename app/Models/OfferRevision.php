<?php

namespace App\Models;

use App\Enums\OfferRevisionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferRevision extends Model
{
    protected $fillable = [
        'payload', 'status', 'reviewer_id', 'comment', 'submitted_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => OfferRevisionStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function supplierOffer(): BelongsTo
    {
        return $this->belongsTo(SupplierOffer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isCostIncrease(): bool
    {
        $newCost = $this->payload['cost_minor'] ?? null;
        $oldCost = $this->payload['previous_cost_minor'] ?? $this->supplierOffer?->cost_minor;

        return $newCost !== null && $oldCost !== null && (int) $newCost > (int) $oldCost;
    }
}
