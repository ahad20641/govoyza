<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\MarkupType;
use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What customers buy: one country + visa type (+ nationality) under Govoyza's own name and price.
 * The suppliers behind it are SupplierOffers and never appear here (CLAUDE.md rule 1).
 * Public site, agent price list, admin catalogue. Task 1.9 — Products and supplier offers.
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

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

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class)->orderBy('sort_order');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Live);
    }
}
