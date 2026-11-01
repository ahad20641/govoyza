<?php

namespace Tests\Feature;

use App\Domain\Catalogue\SupplierOfferWorkflow;
use App\Enums\Currency;
use App\Enums\PartnerStatus;
use App\Enums\ProductStatus;
use App\Enums\SupplierOfferStatus;
use App\Models\Country;
use App\Models\OfferRevision;
use App\Models\Partner;
use App\Models\Product;
use App\Models\VisaType;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

class SupplierOfferWorkflowTest extends TestCase
{
    public function test_a_partner_can_submit_a_draft_offer_for_review(): void
    {
        $partner = $this->partner();
        $offer = app(SupplierOfferWorkflow::class)->createDraft($partner, $this->offerData());

        $this->assertSame(SupplierOfferStatus::Draft, $offer->status);

        app(SupplierOfferWorkflow::class)->submitForReview($partner, $offer);

        $this->assertSame(SupplierOfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_editing_a_live_offer_creates_a_revision_without_changing_live_values(): void
    {
        $partner = $this->partner();
        $offer = app(SupplierOfferWorkflow::class)->createDraft($partner, $this->offerData());
        $offer->status = SupplierOfferStatus::Live;
        $offer->save();

        $revision = app(SupplierOfferWorkflow::class)->updateOffer($partner, $offer, [
            'cost_minor' => 15500,
            'currency' => Currency::USD->value,
            'processing_days' => 7,
            'capacity_per_week' => 12,
            'inclusions' => ['Application review', 'Appointment booking'],
            'refund_terms' => 'Refundable until submission.',
        ]);

        $this->assertInstanceOf(OfferRevision::class, $revision);
        $this->assertSame('pending', $revision->status->value);
        $this->assertSame(15500, $revision->payload['cost_minor']);
        $this->assertSame(SupplierOfferStatus::Live, $offer->fresh()->status);
        $this->assertSame(12500, $offer->fresh()->cost_minor);
        $this->assertSame(Currency::BDT->value, $offer->fresh()->currency);
    }

    public function test_a_partner_cannot_change_another_partners_offer(): void
    {
        $owner = $this->partner();
        $other = $this->partner('Other Supplier Ltd');
        $offer = app(SupplierOfferWorkflow::class)->createDraft($owner, $this->offerData());

        $this->expectException(AuthorizationException::class);

        app(SupplierOfferWorkflow::class)->updateOffer($other, $offer, ['cost_minor' => 1]);
    }

    private function partner(string $name = 'Supplier Ltd'): Partner
    {
        $partner = Partner::create(['legal_name' => $name]);
        $partner->status = PartnerStatus::Active;
        $partner->save();

        return $partner;
    }

    /** @return array<string, mixed> */
    private function offerData(): array
    {
        $country = Country::query()->firstOrFail();
        $visaType = VisaType::query()->firstOrFail();
        $product = Product::create([
            'country_id' => $country->id,
            'visa_type_id' => $visaType->id,
            'public_name' => 'Tourist visa',
            'slug' => 'tourist-visa-test',
            'status' => ProductStatus::Draft,
        ]);

        return [
            'product_id' => $product->id,
            'cost_minor' => 12500,
            'currency' => Currency::BDT->value,
            'processing_days' => 10,
            'capacity_per_week' => 8,
            'inclusions' => ['Application review'],
            'refund_terms' => 'Refundable before processing.',
        ];
    }
}
