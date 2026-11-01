<?php

namespace Database\Factories;

use App\Enums\ServiceJobStatus;
use App\Models\Order;
use App\Models\ServiceJob;
use App\Models\SupplierOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceJob>
 * Tests and the demo seeder only; real jobs come from routing (task 1.20). Sprint S1.
 */
class ServiceJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'supplier_offer_id' => SupplierOffer::factory(),
            'partner_id' => fn (array $attributes) => SupplierOffer::find($attributes['supplier_offer_id'])->partner_id,
            'status' => ServiceJobStatus::Assigned,
            'assigned_at' => now(),
            'sla_due_at' => now()->addDays(10),
            'cost_snapshot_minor' => fn (array $attributes) => SupplierOffer::find($attributes['supplier_offer_id'])->cost_minor,
            'currency' => 'BDT',
        ];
    }
}
