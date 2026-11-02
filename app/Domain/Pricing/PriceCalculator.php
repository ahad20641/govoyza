<?php

namespace App\Domain\Pricing;

use App\Enums\AgencyTier;
use App\Enums\Currency;
use App\Enums\MarkupType;
use App\Enums\OfferPriority;
use App\Enums\OrderChannel;
use App\Enums\ProductStatus;
use App\Enums\SupplierOfferStatus;
use App\Models\Product;
use App\Models\SupplierOffer;
use App\Services\Money\CurrencyConverter;
use App\Support\Money\Money;
use Brick\Math\BigDecimal;
use DomainException;
use InvalidArgumentException;

/**
 * Turns supplier cost + embassy fee + Govoyza markup (+ gateway fee recovery + VAT) into the selling price
 * for a channel/tier/currency. Prices are always calculated here, never typed (CLAUDE.md rule 3).
 * Public site, agent price list, checkout. Task 1.12 — Pricing engine.
 */
class PriceCalculator
{
    public function __construct(private readonly CurrencyConverter $converter) {}

    /** @param string $tier 'retail' or an App\Enums\AgencyTier value */
    public function quote(Product $product, OrderChannel $channel, string $tier, Currency $currency): PriceBreakdown
    {
        if ($product->status !== ProductStatus::Live) {
            throw new DomainException('Only live products can be priced.');
        }

        $this->validateChannelTier($channel, $tier);

        $offer = $this->liveOffer($product);
        $cost = $this->converter->convert($offer->cost, $currency);
        $markup = $this->markup($product, $cost, $channel, $tier, $currency);
        $servicePrice = $cost->plus($markup);

        // Embassy fees are a pass-through and must not include the FX safety buffer.
        $embassyFee = $this->converter->convert(
            $product->embassy_fee ?? Money::zero(Currency::from($product->currency)),
            $currency,
            withBuffer: false,
        );

        $vatRate = $this->percentage(config('govoyza.pricing.vat_rate', '0'), 'VAT rate');
        $vat = $servicePrice->percent($vatRate);

        return new PriceBreakdown(
            servicePrice: $servicePrice,
            embassyFee: $embassyFee,
            vat: $vat,
            total: $servicePrice->plus($embassyFee)->plus($vat),
        );
    }

    private function liveOffer(Product $product): SupplierOffer
    {
        if ($product->relationLoaded('supplierOffers')) {
            $offer = $product->supplierOffers
                ->where('status', SupplierOfferStatus::Live)
                ->sortBy(fn (SupplierOffer $candidate) => match ($candidate->priority) {
                    OfferPriority::Primary => 0,
                    OfferPriority::Backup => 1,
                    default => 2,
                })
                ->first();

            if ($offer) {
                return $offer;
            }
        }

        $offer = $product->supplierOffers()
            ->where('status', SupplierOfferStatus::Live)
            ->orderByRaw("CASE priority WHEN 'primary' THEN 0 WHEN 'backup' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->first();

        if (! $offer) {
            throw new DomainException('This product has no live supplier offer.');
        }

        return $offer;
    }

    private function markup(Product $product, Money $cost, OrderChannel $channel, string $tier, Currency $currency): Money
    {
        $type = $channel === OrderChannel::B2c ? $product->b2c_markup_type : $product->b2b_markup_type;
        $value = $channel === OrderChannel::B2c ? $product->b2c_markup_value : $product->b2b_markup_value;

        $markup = $type === MarkupType::Percent
            ? $cost->percent((int) $value)
            : $this->converter->convert(
                Money::ofMinor((int) $value, Currency::from($product->currency)),
                $currency,
            );

        if ($channel === OrderChannel::B2b) {
            $reductions = config('govoyza.pricing.tier_markup_reductions', []);
            $reductionRate = $this->percentage($reductions[$tier] ?? '0', "{$tier} tier markup reduction");
            $markup = $markup->minus($markup->percent($reductionRate));
        }

        return $markup;
    }

    private function validateChannelTier(OrderChannel $channel, string $tier): void
    {
        if ($channel === OrderChannel::B2c && $tier !== 'retail') {
            throw new InvalidArgumentException('B2C quotes must use the retail tier.');
        }

        if ($channel === OrderChannel::B2b && AgencyTier::tryFrom($tier) === null) {
            throw new InvalidArgumentException('B2B quotes require a valid agency tier.');
        }
    }

    private function percentage(mixed $value, string $name): string
    {
        if (! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException("{$name} must be configured as a decimal string or integer.");
        }

        $rate = (string) $value;

        if (! preg_match('/^\d{1,3}(\.\d{1,4})?$/', $rate) || BigDecimal::of($rate)->isGreaterThan(100)) {
            throw new InvalidArgumentException("{$name} must be between 0 and 100.");
        }

        return $rate;
    }
}
