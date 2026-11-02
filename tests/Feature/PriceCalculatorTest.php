<?php

namespace Tests\Feature;

use App\Domain\Pricing\PriceCalculator;
use App\Enums\AgencyTier;
use App\Enums\Currency;
use App\Enums\MarkupType;
use App\Enums\OfferPriority;
use App\Enums\OrderChannel;
use App\Enums\SupplierOfferStatus;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\SupplierOffer;
use DomainException;
use InvalidArgumentException;
use Tests\TestCase;

/** Pricing fixtures for channel, tier, conversion, and supplier-routing rules. Task 1.12. */
class PriceCalculatorTest extends TestCase
{
    public function test_b2c_price_applies_markup_and_vat_but_keeps_embassy_fee_separate(): void
    {
        config(['govoyza.pricing.vat_rate' => '15']);
        $product = $this->product([
            'b2c_markup_type' => MarkupType::Percent,
            'b2c_markup_value' => 20,
            'embassy_fee_minor' => 3000,
        ]);
        $this->liveOffer($product, ['cost_minor' => 10000, 'currency' => Currency::BDT->value]);

        $breakdown = app(PriceCalculator::class)->quote($product, OrderChannel::B2c, 'retail', Currency::BDT);

        $this->assertSame(12000, $breakdown->servicePrice->minor);
        $this->assertSame(3000, $breakdown->embassyFee->minor);
        $this->assertSame(1800, $breakdown->vat->minor);
        $this->assertSame(16800, $breakdown->total->minor);
    }

    public function test_b2b_tier_reduces_the_product_markup_and_uses_b2b_markup(): void
    {
        config([
            'govoyza.pricing.vat_rate' => '10',
            'govoyza.pricing.tier_markup_reductions' => [
                AgencyTier::Silver->value => '0',
                AgencyTier::Gold->value => '20',
                AgencyTier::Platinum->value => '35',
            ],
        ]);
        $product = $this->product([
            'b2c_markup_type' => MarkupType::Percent,
            'b2c_markup_value' => 50,
            'b2b_markup_type' => MarkupType::Fixed,
            'b2b_markup_value' => 2500,
            'embassy_fee_minor' => 3000,
        ]);
        $this->liveOffer($product, ['cost_minor' => 10000, 'currency' => Currency::BDT->value]);

        $breakdown = app(PriceCalculator::class)->quote($product, OrderChannel::B2b, AgencyTier::Gold->value, Currency::BDT);

        $this->assertSame(12000, $breakdown->servicePrice->minor);
        $this->assertSame(1200, $breakdown->vat->minor);
        $this->assertSame(16200, $breakdown->total->minor);
    }

    public function test_currency_conversion_buffers_supplier_cost_but_passes_embassy_fee_through_without_buffer(): void
    {
        ExchangeRate::create([
            'base' => Currency::BDT,
            'quote' => Currency::USD,
            'rate' => '0.00800000',
            'buffer_pct' => '2.00',
            'effective_at' => now()->subMinute(),
        ]);
        config(['govoyza.pricing.vat_rate' => '10']);
        $product = $this->product([
            'b2c_markup_type' => MarkupType::Percent,
            'b2c_markup_value' => 20,
            'embassy_fee_minor' => 300000,
        ]);
        $this->liveOffer($product, ['cost_minor' => 1000000, 'currency' => Currency::BDT->value]);

        $breakdown = app(PriceCalculator::class)->quote($product, OrderChannel::B2c, 'retail', Currency::USD);

        $this->assertSame(Currency::USD, $breakdown->total->currency);
        $this->assertSame(9792, $breakdown->servicePrice->minor);
        $this->assertSame(2400, $breakdown->embassyFee->minor);
        $this->assertSame(979, $breakdown->vat->minor);
        $this->assertSame(13171, $breakdown->total->minor);
    }

    public function test_primary_live_offer_is_used_before_backup_and_other_offers(): void
    {
        config(['govoyza.pricing.vat_rate' => '0']);
        $product = $this->product(['b2c_markup_value' => 0, 'embassy_fee_minor' => 0]);
        $this->liveOffer($product, [
            'cost_minor' => 9000,
            'currency' => Currency::BDT->value,
            'priority' => OfferPriority::Backup,
        ]);
        $this->liveOffer($product, [
            'cost_minor' => 10000,
            'currency' => Currency::BDT->value,
            'priority' => OfferPriority::Primary,
        ]);

        $breakdown = app(PriceCalculator::class)->quote($product, OrderChannel::B2c, 'retail', Currency::BDT);

        $this->assertSame(10000, $breakdown->servicePrice->minor);
    }

    public function test_products_without_a_live_supplier_offer_cannot_be_quoted(): void
    {
        $product = $this->product();

        $this->expectException(DomainException::class);

        app(PriceCalculator::class)->quote($product, OrderChannel::B2c, 'retail', Currency::BDT);
    }

    public function test_channel_and_tier_must_match(): void
    {
        $product = $this->product();
        $this->liveOffer($product, ['currency' => Currency::BDT->value]);
        $calculator = app(PriceCalculator::class);

        try {
            $calculator->quote($product, OrderChannel::B2c, AgencyTier::Gold->value, Currency::BDT);
            $this->fail('B2C quotes must use the retail tier.');
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);
        }

        $this->expectException(InvalidArgumentException::class);
        $calculator->quote($product, OrderChannel::B2b, 'retail', Currency::BDT);
    }

    public function test_breakdown_snapshot_contains_only_customer_facing_lines(): void
    {
        config(['govoyza.pricing.vat_rate' => '0']);
        $product = $this->product(['b2c_markup_value' => 0, 'embassy_fee_minor' => 3000]);
        $this->liveOffer($product, ['cost_minor' => 10000, 'currency' => Currency::BDT->value]);

        $snapshot = app(PriceCalculator::class)->quote($product, OrderChannel::B2c, 'retail', Currency::BDT)->toArray();

        $this->assertSame(['service_price', 'embassy_fee', 'vat', 'total'], array_keys($snapshot));
        $this->assertSame(['minor' => 10000, 'currency' => 'BDT'], $snapshot['service_price']);
        $this->assertArrayNotHasKey('supplier_cost', $snapshot);
        $this->assertArrayNotHasKey('partner', $snapshot);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create($attributes);
    }

    private function liveOffer(Product $product, array $attributes = []): SupplierOffer
    {
        return SupplierOffer::factory()->for($product)->create([
            'status' => SupplierOfferStatus::Live,
            'priority' => OfferPriority::Primary,
            ...$attributes,
        ]);
    }
}
