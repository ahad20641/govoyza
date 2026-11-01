<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Tests\TestCase;

/** Sprint S1: demo data appears on local/staging only, and re-running it changes nothing. */
class DemoSeederTest extends TestCase
{
    public function test_it_does_nothing_outside_local_and_staging(): void
    {
        $this->app['env'] = 'production';
        app(DemoSeeder::class)->run(); // direct call: `db:seed` would stop to confirm in production

        $this->assertSame(0, Product::count());
        $this->assertNull(User::where('email', DemoSeeder::CUSTOMER_EMAIL)->first());
    }

    public function test_it_seeds_staging_once_and_the_pages_show_it(): void
    {
        $this->app['env'] = 'staging';
        $this->seed(DemoSeeder::class);
        $counts = [Product::count(), Order::count(), User::where('is_demo', true)->count()];
        $this->seed(DemoSeeder::class);

        $this->assertSame($counts, [Product::count(), Order::count(), User::where('is_demo', true)->count()]);
        $this->assertSame([5, 5, 3], $counts);

        $this->get(route('visa.index'))->assertOk()->assertSee('Thailand Tourist Visa')->assertDontSee('Demo Visa Supplier');
        $this->actingAs(User::where('email', DemoSeeder::CUSTOMER_EMAIL)->first())
            ->get(route('account.dashboard'))->assertOk()->assertSee('GV-DEMO-C1');
    }
}
