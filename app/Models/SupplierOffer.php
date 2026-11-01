<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OfferPriority;
use App\Enums\SupplierOfferStatus;
use Database\Factories\SupplierOfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A partner's private offer behind a Product: cost, capacity, priority. Admin and partner portal only;
 * cost and partner are hidden from serialisation (CLAUDE.md rule 1). Tasks 1.9, 1.10.
 */
class SupplierOffer extends Model
{
    /** @use HasFactory<SupplierOfferFactory> */
    use HasFactory;

    protected $hidden = ['partner', 'partner_id', 'cost_minor', 'currency'];

    protected $fillable = [
        'product_id', 'cost_minor', 'currency', 'processing_days', 'capacity_per_week',
        'priority', 'status', 'inclusions', 'refund_terms', 'reviewer_id', 'reviewed_at', 'review_comment',
    ];

    protected function casts(): array
    {
        return [
            'cost' => MoneyCast::class.':cost_minor,currency',
            'priority' => OfferPriority::class,
            'status' => SupplierOfferStatus::class,
            'inclusions' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OfferRevision::class);
    }

    public function serviceJobs(): HasMany
    {
        return $this->hasMany(ServiceJob::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
