<?php

namespace App\Enums;

/** Visa-relevant groupings: one visa covers the whole region, or the region shares rules. */
enum CountryRegion: string
{
    case Schengen = 'schengen';
    case Gcc = 'gcc';

    public function label(): string
    {
        return match ($this) {
            self::Schengen => 'Schengen area',
            self::Gcc => 'Gulf (GCC)',
        };
    }

    /**
     * ISO2 codes of the members. Schengen: 29 states, with Bulgaria and Romania
     * full members since 1 Jan 2025 (Cyprus and Ireland are not in). Update here
     * when membership changes; the reference data seeder re-applies it.
     *
     * @return list<string>
     */
    public function members(): array
    {
        return match ($this) {
            self::Schengen => ['AT', 'BE', 'BG', 'HR', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IS', 'IT', 'LV',
                'LI', 'LT', 'LU', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'CH'],
            self::Gcc => ['AE', 'BH', 'KW', 'OM', 'QA', 'SA'],
        };
    }

    public static function forCountry(string $iso2): ?self
    {
        foreach (self::cases() as $region) {
            if (in_array($iso2, $region->members(), true)) {
                return $region;
            }
        }

        return null;
    }
}
