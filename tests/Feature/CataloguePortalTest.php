<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Enums\PartnerStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleSlug;
use App\Enums\SupplierOfferStatus;
use App\Filament\Partner\Resources\SupplierOffers\Pages\CreateSupplierOffer;
use App\Filament\Partner\Resources\SupplierOffers\Pages\EditSupplierOffer;
use App\Filament\Partner\Resources\SupplierOffers\SupplierOfferResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SupplierOffer;
use App\Models\User;
use App\Models\VisaType;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class CataloguePortalTest extends TestCase
{
    public function test_active_partner_can_create_a_draft_offer_and_only_sees_its_own_offers(): void
    {
        $partner = $this->partner('Supplier A');
        $user = User::factory()->partner()->create(['partner_id' => $partner->id]);
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());
        $otherPartner = $this->partner('Supplier B');
        $product = $this->product();
        $otherOffer = $otherPartner->supplierOffers()->create([
            'product_id' => $product->id,
            'cost_minor' => 9999,
            'currency' => Currency::USD->value,
            'processing_days' => 5,
        ]);

        Filament::setCurrentPanel('partner');
        $this->actingAs($user);

        Livewire::test(CreateSupplierOffer::class)
            ->set('data.product_id', $product->id)
            ->set('data.cost_amount', '125.00')
            ->set('data.currency', Currency::BDT->value)
            ->set('data.processing_days', '10')
            ->set('data.capacity_per_week', '8')
            ->set('data.inclusions', ['Application review'])
            ->set('data.refund_terms', 'Refundable before processing.')
            ->call('create')
            ->assertHasNoFormErrors();

        $offer = SupplierOffer::where('partner_id', $partner->id)->sole();
        $this->assertSame(ProductStatus::Draft, $offer->product->status);
        $this->assertSame('draft', $offer->status->value);
        $this->assertSame(12500, $offer->cost_minor);

        $visibleIds = SupplierOfferResource::getEloquentQuery()->pluck('id')->all();
        $this->assertSame([$offer->id], $visibleIds);
        $this->assertNotContains($otherOffer->id, $visibleIds);
    }

    public function test_only_active_partners_can_access_offer_management(): void
    {
        $pending = $this->partner('Pending Supplier', PartnerStatus::Pending);
        $user = User::factory()->partner()->create(['partner_id' => $pending->id]);
        $this->actingAs($user);
        Filament::setCurrentPanel('partner');

        $this->assertFalse(SupplierOfferResource::canAccess());
    }

    public function test_operations_can_create_a_draft_product(): void
    {
        $country = Country::query()->firstOrFail();
        $country->update(['is_destination' => true]);
        $visaType = VisaType::query()->active()->firstOrFail();
        $operations = User::factory()->role(RoleSlug::Operations)->create();

        $this->actingAs($operations);
        Filament::setCurrentPanel('admin');

        Livewire::test(CreateProduct::class)
            ->set('data.country_id', $country->id)
            ->set('data.visa_type_id', $visaType->id)
            ->set('data.public_name', 'Tourist visa')
            ->set('data.slug', 'tourist-visa-operations-test')
            ->set('data.processing_days_min', '5')
            ->set('data.processing_days_max', '10')
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::sole();
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertTrue(AuditLog::where('action', 'admin.created')->where('auditable_id', $product->id)->exists());

        $finance = User::factory()->role(RoleSlug::Finance)->create();
        $this->actingAs($finance);
        $this->assertFalse(ProductResource::canAccess());
    }

    public function test_editing_a_live_offer_in_the_partner_portal_submits_a_revision(): void
    {
        $partner = $this->partner('Supplier C');
        $user = User::factory()->partner()->create(['partner_id' => $partner->id]);
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());
        $product = $this->product();
        $offer = $partner->supplierOffers()->create([
            'product_id' => $product->id,
            'cost_minor' => 12500,
            'currency' => Currency::BDT->value,
            'processing_days' => 10,
        ]);
        $offer->status = SupplierOfferStatus::Live;
        $offer->save();

        Filament::setCurrentPanel('partner');
        $this->actingAs($user);

        Livewire::test(EditSupplierOffer::class, ['record' => $offer->getRouteKey()])
            ->set('data.cost_amount', '155.00')
            ->set('data.currency', Currency::USD->value)
            ->set('data.processing_days', '7')
            ->call('save')
            ->assertHasNoFormErrors();

        $offer->refresh();
        $this->assertSame(12500, $offer->cost_minor);
        $this->assertSame(Currency::BDT->value, $offer->currency);
        $this->assertSame(1, $offer->revisions()->count());
        $this->assertSame(15500, $offer->revisions()->sole()->payload['cost_minor']);
    }

    public function test_product_and_offer_serialization_do_not_expose_supplier_or_cost_details(): void
    {
        $partner = $this->partner('Secret Supplier Ltd');
        $product = $this->product();
        $offer = $partner->supplierOffers()->create([
            'product_id' => $product->id,
            'cost_minor' => 98765,
            'currency' => Currency::USD->value,
            'processing_days' => 9,
        ]);

        $productJson = $product->load('supplierOffers.partner')->toJson();
        $offerJson = $offer->load('partner')->toJson();

        $this->assertStringNotContainsString('Secret Supplier Ltd', $productJson);
        $this->assertStringNotContainsString('98765', $productJson);
        $this->assertStringNotContainsString('Secret Supplier Ltd', $offerJson);
        $this->assertStringNotContainsString('98765', $offerJson);
        $this->assertStringNotContainsString('partner_id', $offerJson);
    }

    private function partner(string $name, PartnerStatus $status = PartnerStatus::Active): Partner
    {
        $partner = Partner::create(['legal_name' => $name]);
        $partner->status = $status;
        $partner->save();

        return $partner;
    }

    private function product(): Product
    {
        return Product::create([
            'country_id' => Country::query()->firstOrFail()->id,
            'visa_type_id' => VisaType::query()->firstOrFail()->id,
            'public_name' => 'Tourist visa',
            'slug' => 'tourist-visa-portal-test',
            'status' => ProductStatus::Draft,
        ]);
    }
}
