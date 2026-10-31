<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Country extends Model
{
    protected $fillable = ['name', 'iso2', 'iso3', 'flag', 'image_path', 'description', 'is_destination', 'is_popular', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_destination' => 'boolean', 'is_popular' => 'boolean', 'is_active' => 'boolean', 'is_demo' => 'boolean'];
    }

    public function nationality(): HasOne
    {
        return $this->hasOne(Nationality::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }
}
