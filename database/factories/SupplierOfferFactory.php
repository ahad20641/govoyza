<?php

namespace Database\Factories;

use App\Enums\OfferPriority;
use App\Enums\SupplierOfferStatus;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SupplierOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierOffer>
 * Tests and the demo seeder only. Sprint S1.
 */
class SupplierOfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'partner_id' => Partner::factory(),
            'cost_minor' => fake()->numberBetween(5_000, 40_000) * 100,
            'currency' => 'BDT',
            'processing_days' => 10,
            'capacity_per_week' => 20,
            'priority' => OfferPriority::Primary,
            'status' => SupplierOfferStatus::Live,
            'inclusions' => ['Document check', 'Form filling', 'Appointment booking'],
            'refund_terms' => 'Full refund before submission.',
        ];
    }
}
