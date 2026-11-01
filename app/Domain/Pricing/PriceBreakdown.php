<?php

namespace App\Domain\Pricing;

use App\Support\Money\Money;

/**
 * The priced result for one product, channel and tier: what the customer sees, line by line.
 * Stored on the order as `price_snapshot` at `placed` and never changed after. Used by the public site,
 * agent price list and checkout. Task 1.12 — Pricing engine.
 *
 * Holds no supplier cost on purpose: this object reaches customers (CLAUDE.md rule 1).
 */
final readonly class PriceBreakdown
{
    public function __construct(
        public Money $servicePrice,
        public Money $embassyFee,     // pass-through at cost, always shown separately
        public Money $vat,
        public Money $total,
    ) {}

    /** @return array<string, array{minor: int, currency: string}> */
    public function toArray(): array
    {
        // TODO(1.12): settle the snapshot format with the order placement task (1.17).
        return [];
    }
}
