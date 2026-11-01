<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Enums\MarkupType;
use App\Enums\OfferPriority;
use App\Enums\PartnerStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleSlug;
use App\Enums\SupplierOfferStatus;
use App\Filament\Partner\Resources\SupplierOffers\Pages\ListSupplierOffers;
use App\Filament\Resources\SupplierOfferReviews\Pages\ViewSupplierOffer;
use App\Filament\Resources\SupplierOfferReviews\SupplierOfferReviewResource;
use App\Models\Country;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SupplierOffer;
use App\Models\User;
use App\Models\VisaType;
use App\Services\Catalogue\SupplierOfferWorkflow;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class OfferReviewPortalTest extends TestCase
{
    public function test_only_catalogue_staff_can_open_the_offer_review_queue(): void
    {
        $operations = User::factory()->role(RoleSlug::Operations)->create();
        $operations->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());
        $finance = User::factory()->role(RoleSlug::Finance)->create();
        $finance->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());

        $this->actingAs($operations);
        Filament::setCurrentPanel('admin');
        $this->assertTrue(SupplierOfferReviewResource::canAccess());
        $this->get(SupplierOfferReviewResource::getUrl())->assertOk();

        $this->actingAs($finance);
        $this->assertFalse(SupplierOfferReviewResource::canAccess());
        $this->get(SupplierOfferReviewResource::getUrl())->assertForbidden();
    }

    public function test_review_detail_compares_offers_for_the_same_country_visa_type_and_nationality(): void
    {
        $country = Country::query()->firstOrFail();
        $otherNationality = Country::query()->whereKeyNot($country->id)->firstOrFail();
        $visaType = VisaType::query()->firstOrFail();
        $otherVisaType = VisaType::query()->whereKeyNot($visaType->id)->firstOrFail();
        $product = $this->product($country, $visaType, 'Tourist visa', $country->iso2);
        $sameProduct = $this->product($country, $visaType, 'Tourist visa - another supplier', $country->iso2);
        $differentProduct = $this->product($country, $otherVisaType, 'Medical visa');
        $differentNationalityProduct = $this->product($country, $visaType, 'Tourist visa - other nationality', $otherNationality->iso2);
        $reviewOffer = $this->offer($product, 'Supplier Under Review Ltd', SupplierOfferStatus::Pending);
        $this->offer($sameProduct, 'Comparable Supplier Ltd', SupplierOfferStatus::Live);
        $this->offer($differentProduct, 'Different Visa Supplier Ltd', SupplierOfferStatus::Live);
        $this->offer($differentNationalityProduct, 'Different Nationality Supplier Ltd', SupplierOfferStatus::Live);

        $this->actingAs(User::factory()->role(RoleSlug::Operations)->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewSupplierOffer::class, ['record' => $reviewOffer->getRouteKey()])
            ->assertSee('Supplier Under Review Ltd')
            ->assertSee('Comparable Supplier Ltd')
            ->assertDontSee('Different Visa Supplier Ltd')
            ->assertDontSee('Different Nationality Supplier Ltd');
    }

    public function test_operations_can_approve_a_pending_offer_from_the_review_page(): void
    {
        $product = $this->product(Country::query()->firstOrFail(), VisaType::query()->firstOrFail(), 'Tourist visa');
        $offer = $this->offer($product, 'Supplier Ltd', SupplierOfferStatus::Pending);
        $this->actingAs(User::factory()->role(RoleSlug::Operations)->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewSupplierOffer::class, ['record' => $offer->getRouteKey()])
            ->callAction('approveOffer', data: [
                'public_name' => 'Tourist visa',
                'description' => 'A managed tourist visa application.',
                'b2c_markup_type' => MarkupType::Percent->value,
                'b2c_markup_value' => 18,
                'b2b_markup_type' => MarkupType::Fixed->value,
                'b2b_markup_value' => '25.00',
                'priority' => OfferPriority::Primary->value,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(SupplierOfferStatus::Live, $offer->fresh()->status);
        $this->assertSame(ProductStatus::Live, $product->fresh()->status);
        $this->assertSame('Tourist visa', $product->fresh()->public_name);
        $this->assertSame(2500, $product->fresh()->b2b_markup_value);
    }

    public function test_review_page_flags_and_approves_a_pending_cost_increase_revision(): void
    {
        $product = $this->product(Country::query()->firstOrFail(), VisaType::query()->firstOrFail(), 'Live tourist visa');
        $partner = $this->partner('Supplier Ltd');
        $offer = $partner->supplierOffers()->create([
            'product_id' => $product->id,
            'cost_minor' => 12000,
            'currency' => Currency::BDT->value,
            'processing_days' => 10,
            'status' => SupplierOfferStatus::Live,
        ]);
        $revision = app(SupplierOfferWorkflow::class)->updateOffer($partner, $offer, [
            'cost_minor' => 15000,
            'currency' => Currency::BDT->value,
            'processing_days' => 8,
        ]);
        $this->actingAs(User::factory()->role(RoleSlug::Operations)->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewSupplierOffer::class, ['record' => $offer->getRouteKey()])
            ->assertSee('Cost increase')
            ->callAction('approveRevision', data: ['revision_id' => $revision->id])
            ->assertHasNoActionErrors();

        $this->assertSame(15000, $offer->fresh()->cost_minor);
        $this->assertTrue($revision->fresh()->isCostIncrease());
    }

    public function test_review_queue_only_contains_pending_offers_and_pending_revisions(): void
    {
        $country = Country::query()->firstOrFail();
        $visaType = VisaType::query()->firstOrFail();
        $pending = $this->offer($this->product($country, $visaType, 'Pending product'), 'Pending Supplier Ltd', SupplierOfferStatus::Pending);
        $partner = $this->partner('Existing Supplier Ltd');
        $live = $partner->supplierOffers()->create([
            'product_id' => $this->product($country, $visaType, 'Live product')->id,
            'cost_minor' => 12000,
            'currency' => Currency::BDT->value,
            'processing_days' => 10,
            'status' => SupplierOfferStatus::Live,
        ]);
        $draft = $partner->supplierOffers()->create([
            'product_id' => $this->product($country, $visaType, 'Draft product')->id,
            'cost_minor' => 11000,
            'currency' => Currency::BDT->value,
            'processing_days' => 9,
            'status' => SupplierOfferStatus::Draft,
        ]);
        app(SupplierOfferWorkflow::class)->updateOffer($partner, $live, [
            'cost_minor' => 13000,
            'currency' => Currency::BDT->value,
            'processing_days' => 8,
        ]);
        $this->actingAs(User::factory()->role(RoleSlug::Operations)->create());
        Filament::setCurrentPanel('admin');

        $ids = SupplierOfferReviewResource::getEloquentQuery()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$pending->id, $live->id], $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_request_changes_action_records_a_comment_for_the_partner(): void
    {
        $offer = $this->offer(
            $this->product(Country::query()->firstOrFail(), VisaType::query()->firstOrFail(), 'Tourist visa'),
            'Supplier Ltd',
            SupplierOfferStatus::Pending,
        );
        $this->actingAs(User::factory()->role(RoleSlug::Operations)->create());
        Filament::setCurrentPanel('admin');

        Livewire::test(ViewSupplierOffer::class, ['record' => $offer->getRouteKey()])
            ->callAction('requestChanges', data: ['comment' => 'Clarify the refund terms.'])
            ->assertHasNoActionErrors();

        $this->assertSame(SupplierOfferStatus::ChangesRequested, $offer->fresh()->status);
        $this->assertSame('Clarify the refund terms.', $offer->fresh()->review_comment);
    }

    public function test_partner_sees_the_review_comment_on_its_offer_list(): void
    {
        $offer = $this->offer(
            $this->product(Country::query()->firstOrFail(), VisaType::query()->firstOrFail(), 'Tourist visa'),
            'Supplier Ltd',
            SupplierOfferStatus::ChangesRequested,
        );
        $offer->review_comment = 'Clarify the refund terms.';
        $offer->save();
        $user = User::factory()->partner()->create(['partner_id' => $offer->partner_id]);
        $user->saveAppAuthenticationSecret(AppAuthentication::make()->generateSecret());
        $this->actingAs($user);
        Filament::setCurrentPanel('partner');

        Livewire::test(ListSupplierOffers::class)->assertSee('Clarify the refund terms.');
    }

    private function product(Country $country, VisaType $visaType, string $name, ?string $nationality = null): Product
    {
        return Product::create([
            'country_id' => $country->id,
            'visa_type_id' => $visaType->id,
            'nationality_iso2' => $nationality,
            'public_name' => $name,
            'slug' => str()->slug($name).'-'.str()->random(8),
            'status' => ProductStatus::Draft,
        ]);
    }

    private function offer(Product $product, string $name, SupplierOfferStatus $status): SupplierOffer
    {
        $partner = $this->partner($name);

        return $partner->supplierOffers()->create([
            'product_id' => $product->id,
            'cost_minor' => 15000,
            'currency' => Currency::BDT->value,
            'processing_days' => 9,
            'status' => $status,
        ]);
    }

    private function partner(string $name): Partner
    {
        $partner = Partner::create(['legal_name' => $name]);
        $partner->status = PartnerStatus::Active;
        $partner->save();

        return $partner;
    }
}
