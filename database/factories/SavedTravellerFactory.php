<?php

namespace Database\Factories;

use App\Models\SavedTraveller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedTraveller>
 * Tests and the demo seeder only. Sprint S1.
 */
class SavedTravellerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'given_names' => fake()->firstName(),
            'surname' => fake()->lastName(),
            'passport_number' => 'DEMO'.fake()->numerify('#####'),
            'nationality_iso2' => 'BD',
            'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
            'passport_expiry' => now()->addYears(5)->toDateString(),
        ];
    }
}
