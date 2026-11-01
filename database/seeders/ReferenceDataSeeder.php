<?php

namespace Database\Seeders;

use App\Enums\CountryRegion;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * All countries + nationalities (database/data/countries.php), the 7 visa
 * types and the visa formats. Safe to re-run (post-deploy runs it every time):
 * it refreshes names, codes and regions, adds missing rows, and never touches
 * what admins edit (destination/popular/active flags, descriptions, SEO, order,
 * slugs of existing rows).
 */
class ReferenceDataSeeder extends Seeder
{
    /** slug => [name, description] */
    public const VISA_TYPES = [
        'tourist' => ['Tourist', 'Holidays, sightseeing and visiting for leisure.'],
        'medical' => ['Medical', 'Travelling for medical treatment, with or without an attendant.'],
        'business' => ['Business', 'Meetings, conferences and trade visits. Not for paid work.'],
        'student' => ['Student', 'Studying at a school, college or university.'],
        'work' => ['Work', 'Employment with an employer in the destination country.'],
        'transit' => ['Transit', 'Passing through on the way to another country.'],
        'family' => ['Family visit', 'Visiting relatives or a spouse who live in the destination country.'],
    ];

    /** slug => [name, requires physical passport] */
    public const VISA_FORMATS = [
        'evisa' => ['E-visa', false],
        'sticker' => ['Sticker visa', true],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $this->countries();
            $this->visaTypes();
            $this->visaFormats();
        });
    }

    private function countries(): void
    {
        $rows = require database_path('data/countries.php');
        $now = now();

        Country::query()->upsert(
            array_map(fn (array $r) => [
                'iso2' => $r[0],
                'iso3' => $r[1],
                'name' => $r[2],
                'continent' => $r[3],
                'region' => CountryRegion::forCountry($r[0])?->value,
                'slug' => Str::slug($r[2]),
                'flag' => Country::flagFor($r[0]),
                'created_at' => $now,
                'updated_at' => $now,
            ], $rows),
            uniqueBy: ['iso2'],
            // Not slug: existing URLs must not change if a name is corrected.
            update: ['iso3', 'name', 'continent', 'region', 'flag', 'updated_at'],
        );

        $ids = Country::query()->pluck('id', 'iso2');
        $home = (string) config('govoyza.company.default_country');

        DB::table('nationalities')->insertOrIgnore(array_values(array_map(fn (array $r) => [
            'country_id' => $ids[$r[0]],
            'demonym' => $r[4],
            'is_active' => true,
            // The home country's passport is listed first in forms.
            'sort_order' => $r[0] === $home ? 1 : 100,
            'created_at' => $now,
            'updated_at' => $now,
        ], array_filter($rows, fn (array $r) => $r[4] !== null))));
    }

    private function visaTypes(): void
    {
        $order = 0;
        foreach (self::VISA_TYPES as $slug => [$name, $description]) {
            DB::table('visa_types')->insertOrIgnore([
                'slug' => $slug, 'name' => $name, 'description' => $description, 'is_active' => true,
                'sort_order' => ++$order, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function visaFormats(): void
    {
        $order = 0;
        foreach (self::VISA_FORMATS as $slug => [$name, $physical]) {
            DB::table('visa_formats')->insertOrIgnore([
                'slug' => $slug, 'name' => $name, 'requires_physical_passport' => $physical, 'is_active' => true,
                'sort_order' => ++$order, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
