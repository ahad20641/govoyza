<?php

namespace Database\Factories;

use App\Enums\MarkupType;
use App\Enums\ProductStatus;
use App\Models\Country;
use App\Models\Product;
use App\Models\VisaType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 * Needs the reference data (countries, visa types) seeded first. Tests and demo seeder. Sprint S1.
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $country = Country::query()->where('is_active', true)->inRandomOrder()->firstOrFail();
        $visaType = VisaType::query()->inRandomOrder()->firstOrFail();

        return [
            'country_id' => $country->id,
            'visa_type_id' => $visaType->id,
            'nationality_iso2' => null,
            'public_name' => "{$country->name} {$visaType->name} Visa",
            'description' => 'Full application support from document check to submission.',
            'processing_days_min' => 5,
            'processing_days_max' => 15,
            'validity' => '90 days',
            'b2c_markup_type' => MarkupType::Percent,
            'b2c_markup_value' => 20,
            'b2b_markup_type' => MarkupType::Percent,
            'b2b_markup_value' => 10,
            'embassy_fee_minor' => 900000,
            'currency' => 'BDT',
            'status' => ProductStatus::Live,
            'slug' => Str::slug("{$country->slug} {$visaType->slug}").'-'.Str::lower(Str::random(5)),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft]);
    }
}
