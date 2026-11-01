<?php

namespace Tests\Feature;

use App\Domain\Catalogue\OfferReviewWorkflow;
use App\Domain\Catalogue\SupplierOfferWorkflow;
use App\Enums\Currency;
use App\Enums\MarkupType;
use App\Enums\OfferPriority;
use App\Enums\OfferRevisionStatus;
use App\Enums\PartnerStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleSlug;
use App\Enums\SupplierOfferStatus;
use App\Models\Country;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use App\Models\VisaType;
use DomainException;
use Tests\TestCase;

class OfferReviewWorkflowTest extends TestCase
{
    public function test_approving_a_pending_offer_publishes_the_product_and_prioritizes_the_supplier(): void
    {
        $product = $this->product();
        $existingPartner = $this->partner('Existing Supplier Ltd');
        $existing = $existingPartner->supplierOffers()->create([
            'product_id' => $product->id,
            'cost_minor' => 18000,
            'currency' => Currency::BDT->value,
            'processing_days' => 12,
            'priority' => OfferPriority::Primary->value,
            'status' => SupplierOfferStatus::Live,
        ]);
        $partner = $this->partner('New Supplier Ltd');
        $offer = $partner->supplierOffers()->create([
            'product_id' => $product->id,
            'cost_minor' => 15000,
            'currency' => Currency::BDT->value,
            'processing_days' => 9,
            'status' => SupplierOfferStatus::Pending,
        ]);
        $reviewer = User::factory()->role(RoleSlug::Operations)->create();

        app(OfferReviewWorkflow::class)->approveOffer($offer, $reviewer, [
            'public_name' => 'Tourist visa',
            'description' => 'A managed tourist visa application.',
            'b2c_markup_type' => MarkupType::Percent->value,
            'b2c_markup_value' => 18,
            'b2b_markup_type' => MarkupType::Fixed->value,
            'b2b_markup_value' => 2500,
            'priority' => OfferPriority::Primary->value,
        ]);

        $this->assertSame(SupplierOfferStatus::Live, $offer->fresh()->status);
        $this->assertSame(OfferPriority::Primary, $offer->fresh()->priority);
        $this->assertSame(OfferPriority::Backup, $existing->fresh()->priority);
        $this->assertSame(ProductStatus::Live, $product->fresh()->status);
        $this->assertSame('Tourist visa', $product->fresh()->public_name);
        $this->assertSame('A managed tourist visa application.', $product->fresh()->description);
        $this->assertSame(18, $product->fresh()->b2c_markup_value);
        $this->assertSame(2500, $product->fresh()->b2b_markup_value);
        $this->assertSame($reviewer->id, $offer->fresh()->reviewer_id);
    }

    public function test_requesting_changes_returns_an_offer_to_the_partner_but_rejection_is_final(): void
    {
        $partner = $this->partner('Supplier Ltd');
        $offer = $partner->supplierOffers()->create([
            'product_id' => $this->product()->id,
            'cost_minor' => 15000,
            'currency' => Currency::BDT->value,
            'processing_days' => 9,
            'status' => SupplierOfferStatus::Pending,
        ]);
        $reviewer = User::factory()->role(RoleSlug::Operations)->create();
        $workflow = app(OfferReviewWorkflow::class);

        $workflow->requestOfferChanges($offer, $reviewer, 'Clarify the refund terms.');

        $this->assertSame(SupplierOfferStatus::ChangesRequested, $offer->fresh()->status);
        $this->assertSame('Clarify the refund terms.', $offer->fresh()->review_comment);

        $supplierWorkflow = app(SupplierOfferWorkflow::class);
        $supplierWorkflow->updateOffer($partner, $offer->fresh(), ['refund_terms' => 'Updated terms.']);
        $supplierWorkflow->submitForReview($partner, $offer->fresh());
        $workflow->rejectOffer($offer->fresh(), $reviewer, 'The supplier does not meet requirements.');

        $this->assertSame(SupplierOfferStatus::Rejected, $offer->fresh()->status);
        $this->assertSame('The supplier does not meet requirements.', $offer->fresh()->review_comment);

        $this->expectException(DomainException::class);
        $workflow->requestOfferChanges($offer->fresh(), $reviewer, 'Try again.');
    }

    public function test_approving_a_revision_applies_its_payload_and_preserves_the_cost_increase_flag(): void
    {
        $partner = $this->partner('Supplier Ltd');
        $offer = $partner->supplierOffers()->create([
            'product_id' => $this->product()->id,
            'cost_minor' => 12000,
            'currency' => Currency::BDT->value,
            'processing_days' => 10,
            'status' => SupplierOfferStatus::Live,
        ]);
        $revision = app(SupplierOfferWorkflow::class)->updateOffer($partner, $offer, [
            'cost_minor' => 15000,
            'currency' => Currency::BDT->value,
            'processing_days' => 8,
            'capacity_per_week' => 12,
            'inclusions' => ['Application review'],
            'refund_terms' => 'Refundable before submission.',
        ]);
        $reviewer = User::factory()->role(RoleSlug::Operations)->create();

        $this->assertTrue($revision->isCostIncrease());

        app(OfferReviewWorkflow::class)->approveRevision($revision, $reviewer);

        $this->assertSame(15000, $offer->fresh()->cost_minor);
        $this->assertSame(8, $offer->fresh()->processing_days);
        $this->assertSame(OfferRevisionStatus::Approved, $revision->fresh()->status);
        $this->assertTrue($revision->fresh()->isCostIncrease());
        $this->assertSame($reviewer->id, $revision->fresh()->reviewer_id);
    }

    public function test_revision_changes_and_rejections_are_recorded_without_touching_live_terms(): void
    {
        $partner = $this->partner('Supplier Ltd');
        $offer = $partner->supplierOffers()->create([
            'product_id' => $this->product()->id,
            'cost_minor' => 12000,
            'currency' => Currency::BDT->value,
            'processing_days' => 10,
            'status' => SupplierOfferStatus::Live,
        ]);
        $revision = $offer->revisions()->create([
            'payload' => ['cost_minor' => 13000, 'currency' => Currency::BDT->value],
            'status' => OfferRevisionStatus::Pending,
            'submitted_at' => now(),
        ]);
        $reviewer = User::factory()->role(RoleSlug::Operations)->create();
        $workflow = app(OfferReviewWorkflow::class);

        $workflow->requestRevisionChanges($revision, $reviewer, 'Explain the cost increase.');

        $this->assertSame(OfferRevisionStatus::ChangesRequested, $revision->fresh()->status);
        $this->assertSame('Explain the cost increase.', $revision->fresh()->comment);
        $this->assertSame(12000, $offer->fresh()->cost_minor);

        $newRevision = $offer->revisions()->create([
            'payload' => ['cost_minor' => 13000, 'currency' => Currency::BDT->value],
            'status' => OfferRevisionStatus::Pending,
            'submitted_at' => now(),
        ]);
        $workflow->rejectRevision($newRevision, $reviewer, 'Cost is out of range.');

        $this->assertSame(OfferRevisionStatus::Rejected, $newRevision->fresh()->status);
        $this->assertSame('Cost is out of range.', $newRevision->fresh()->comment);
        $this->assertSame(12000, $offer->fresh()->cost_minor);
    }

    private function partner(string $name): Partner
    {
        $partner = Partner::create(['legal_name' => $name]);
        $partner->status = PartnerStatus::Active;
        $partner->save();

        return $partner;
    }

    private function product(): Product
    {
        return Product::create([
            'country_id' => Country::query()->firstOrFail()->id,
            'visa_type_id' => VisaType::query()->firstOrFail()->id,
            'public_name' => 'Draft visa',
            'slug' => 'draft-visa-'.str()->random(8),
            'status' => ProductStatus::Draft,
        ]);
    }
}
