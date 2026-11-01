<?php

namespace App\Enums;

/**
 * B2B pricing tier; upgrades come from monthly volume (Phase 2). Agent portal and pricing. Task 1.12 — Pricing engine.
 */
enum AgencyTier: string
{
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
