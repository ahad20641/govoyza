<?php

namespace App\Models;

use App\Enums\Continent;
use App\Enums\CountryRegion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Reference data: names, codes, region and continent come from the seeder
 * (database/data/countries.php). Admins edit only the destination flags,
 * description and SEO fields.
 */
class Country extends Model
{
    protected $fillable = [
        'name', 'iso2', 'iso3', 'slug', 'region', 'continent', 'flag', 'image_path',
        'description', 'seo_title', 'seo_description', 'is_destination', 'is_popular', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'region' => CountryRegion::class,
            'continent' => Continent::class,
            'is_destination' => 'boolean',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    /** Countries GoVoyza sells visas for. */
    public function scopeDestinations(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('is_destination', true);
    }

    /** Regional-indicator emoji for an ISO2 code, e.g. BD → 🇧🇩. */
    public static function flagFor(string $iso2): string
    {
        return implode('', array_map(
            fn (string $letter) => mb_chr(0x1F1E6 + ord($letter) - ord('A')),
            str_split(strtoupper($iso2)),
        ));
    }
}
