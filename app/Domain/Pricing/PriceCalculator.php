<?php

namespace App\Domain\Pricing;

use App\Domain\NotBuiltYet;
use App\Enums\Currency;
use App\Enums\OrderChannel;
use App\Models\Product;

/**
 * Turns supplier cost + embassy fee + Govoyza markup (+ gateway fee recovery + VAT) into the selling price
 * for a channel/tier/currency. Prices are always calculated here, never typed (CLAUDE.md rule 3).
 * Public site, agent price list, checkout. Task 1.12 — Pricing engine.
 */
class PriceCalculator
{
    /** @param string $tier 'retail' or an App\Enums\AgencyTier value */
    public function quote(Product $product, OrderChannel $channel, string $tier, Currency $currency): PriceBreakdown
    {
        // TODO(1.12): primary live offer's cost → markup (product b2c/b2b, tier discount) → VAT → convert with
        // CurrencyConverter (admin rate + buffer). Round once per line (Money rounding rules).
        throw NotBuiltYet::task('1.12', 'The pricing engine');
    }
}
