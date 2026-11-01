<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Applicant>
 * Tests and the demo seeder only. Passport numbers are obviously fake. Sprint S1.
 */
class ApplicantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'given_names' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'passport_number' => 'DEMO'.fake()->numerify('#####'),
            'nationality_iso2' => 'BD',
            'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
            'passport_expiry' => now()->addYears(5)->toDateString(),
        ];
    }
}
