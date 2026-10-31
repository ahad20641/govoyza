<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends Model
{
    protected $fillable = ['country_id', 'name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
