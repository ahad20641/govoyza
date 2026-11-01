<?php

namespace Database\Factories;

use App\Enums\AgencyStatus;
use App\Enums\AgencyTier;
use App\Enums\RoleSlug;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Agency>
 * Tests and the demo seeder only. Sprint S1.
 */
class AgencyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company().' Travels';

        return [
            'user_id' => User::factory()->role(RoleSlug::Agency),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'trade_license_number' => fake()->numerify('TRAD/DNCC/######/2026'),
            'business_email' => fake()->unique()->companyEmail(),
            'business_phone' => '+8801'.fake()->numerify('#########'),
            'address' => fake()->address(),
            'status' => AgencyStatus::Approved,
            'approved_at' => now(),
            'tier' => AgencyTier::Silver,
            'credit_limit_minor' => 0,
            'currency' => 'BDT',
        ];
    }
}
