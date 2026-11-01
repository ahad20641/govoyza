<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\MarkupType;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $hidden = ['supplier_offers'];

    protected $fillable = [
        'country_id', 'visa_type_id', 'nationality_iso2', 'public_name', 'description',
        'processing_days_min', 'processing_days_max', 'validity', 'b2c_markup_type', 'b2c_markup_value',
        'b2b_markup_type', 'b2b_markup_value', 'embassy_fee_minor', 'currency', 'status', 'slug',
    ];

    protected function casts(): array
    {
        return [
            'b2c_markup_type' => MarkupType::class,
            'b2b_markup_type' => MarkupType::class,
            'embassy_fee' => MoneyCast::class.':embassy_fee_minor,currency',
            'status' => ProductStatus::class,
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function visaType(): BelongsTo
    {
        return $this->belongsTo(VisaType::class);
    }

    public function supplierOffers(): HasMany
    {
        return $this->hasMany(SupplierOffer::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Live);
    }
}
