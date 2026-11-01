<?php

namespace Database\Factories;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Agency;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 * Writes status and totals directly, which only tests and the demo seeder may do; real orders
 * go through App\Domain\Orders\OrderStateMachine and the pricing engine. Sprint S1.
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'GV-'.now()->format('ym').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'agency_id' => null,
            'product_id' => Product::factory(),
            'channel' => OrderChannel::B2c,
            'tier' => 'retail',
            'status' => OrderStatus::Paid,
            'price_snapshot' => null, // TODO(1.12): PriceBreakdown::toArray() once the pricing engine exists
            'total_minor' => fake()->numberBetween(10_000, 60_000) * 100,
            'currency' => 'BDT',
            'travel_date' => now()->addMonths(2)->toDateString(),
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function forAgency(Agency $agency): static
    {
        return $this->state(fn () => [
            'user_id' => $agency->user_id,
            'agency_id' => $agency->id,
            'channel' => OrderChannel::B2b,
            'tier' => $agency->tier->value,
        ]);
    }
}
