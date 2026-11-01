<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Enums\PartnerStatus;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 * Tests and the demo seeder only. Sprint S1.
 */
class PartnerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'legal_name' => fake()->company().' Visa Services',
            'contact_phone' => '+8801'.fake()->numerify('#########'),
            'status' => PartnerStatus::Active,
            'kyc_status' => KycStatus::Approved,
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => PartnerStatus::Pending, 'kyc_status' => KycStatus::NotSubmitted, 'approved_at' => null]);
    }
}
