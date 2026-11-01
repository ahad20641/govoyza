<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Http\Controllers\Public\PageController;
use App\Models\Agency;
use App\Models\Applicant;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierOffer;
use App\Models\User;
use Tests\TestCase;

/**
 * Sprint S1 smoke tests for govoyza.com: every public page renders, and each portal route answers
 * 200 for its own account type, 403 for the others and 302 (to login) for guests.
 * Full behaviour tests arrive with each area's task.
 */
class SkeletonSmokeTest extends TestCase
{
    // ==================== Helpers ====================

    /** @return list<string> every /account URL for this customer */
    private function accountUrls(Order $order): array
    {
        return [
            route('account.dashboard'),
            route('account.applications.index'),
            route('account.applications.show', $order->number),
            route('account.travellers.index'),
            route('account.payments.index'),
            route('account.support.index'),
            route('account.profile.show'),
        ];
    }

    /** @return list<string> */
    private function agentUrls(): array
    {
        return [
            route('agent.dashboard'),
            route('agent.prices.index'),
            route('agent.applications.create'),
            route('agent.applications.bulk'),
            route('agent.wallet.show'),
            route('agent.users.index'),
            route('agent.reports.index'),
        ];
    }

    // ==================== Public site ====================

    public function test_every_public_page_renders_for_guests(): void
    {
        $product = Product::factory()->create();
        $product->load(['country', 'visaType']);

        $urls = [
            route('home'),
            route('visa.index'),
            route('visa.index', ['destination' => $product->country->slug, 'purpose' => $product->visaType->slug]),
            route('visa.country', $product->country),
            route('visa.type', [$product->country, $product->visaType]),
            route('visa.service', $product),
            route('track'),
        ];

        foreach (array_keys(PageController::PAGES) as $slug) {
            $urls[] = route("pages.{$slug}");
        }

        foreach ($urls as $url) {
            $this->get($url)->assertOk()->assertSee('GO', false);
        }

        $this->post(route('track.lookup'), ['number' => 'GV-X', 'email' => 'a@example.com'])->assertRedirect();
    }

    public function test_draft_products_are_not_public(): void
    {
        $this->get(route('visa.service', Product::factory()->draft()->create()))->assertNotFound();
    }

    public function test_public_pages_never_show_partner_names_or_costs(): void
    {
        $offer = SupplierOffer::factory()->create();
        $product = $offer->product->load(['country', 'visaType']);

        foreach ([route('visa.index'), route('visa.country', $product->country), route('visa.service', $product)] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee($offer->partner->legal_name)
                ->assertDontSee($offer->cost->format());
        }
    }

    // ==================== Customer portal /account ====================

    public function test_customer_portal_answers_by_account_type(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer)->create();
        Applicant::factory()->for($order)->create();

        foreach ($this->accountUrls($order) as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        foreach ($this->accountUrls($order) as $url) {
            $this->actingAs($customer)->get($url)->assertOk();
        }

        $others = [
            User::factory()->agency()->create(),
            User::factory()->partner()->create(),
            User::factory()->owner()->create(),
        ];

        foreach ($others as $user) {
            foreach ($this->accountUrls($order) as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }

    public function test_customers_cannot_open_each_others_applications(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('account.applications.show', $order->number))
            ->assertNotFound();
    }

    // ==================== Agent portal /agent ====================

    public function test_agent_portal_answers_by_account_type(): void
    {
        $agency = Agency::factory()->create();
        Order::factory()->forAgency($agency)->create();
        $agent = $agency->owner;

        foreach ($this->agentUrls() as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        foreach ($this->agentUrls() as $url) {
            $this->actingAs($agent)->get($url)->assertOk();
        }

        foreach ([User::factory()->create(), User::factory()->role(RoleSlug::Partner)->create(), User::factory()->finance()->create()] as $user) {
            foreach ($this->agentUrls() as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }
}
