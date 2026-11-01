<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OfferPriority;
use App\Enums\SupplierOfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierOffer extends Model
{
    protected $hidden = ['partner', 'partner_id', 'cost_minor', 'currency'];

    protected $fillable = [
        'product_id', 'cost_minor', 'currency', 'processing_days', 'capacity_per_week',
        'priority', 'status', 'inclusions', 'refund_terms',
    ];

    protected function casts(): array
    {
        return [
            'cost' => MoneyCast::class.':cost_minor,currency',
            'priority' => OfferPriority::class,
            'status' => SupplierOfferStatus::class,
            'inclusions' => 'array',
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
}
