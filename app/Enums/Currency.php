<?php

namespace App\Enums;

enum Currency: string
{
    case BDT = 'BDT';
    case USD = 'USD';
    case EUR = 'EUR';

    /** Digits after the decimal point; amounts are stored as integers of 10^-digits. */
    public function minorUnits(): int
    {
        return match ($this) {
            self::BDT, self::USD, self::EUR => 2,
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::BDT => '৳',
            self::USD => '$',
            self::EUR => '€',
        };
    }

    /** BDT uses South Asian (lakh/crore) digit grouping. */
    public function usesLakhGrouping(): bool
    {
        return $this === self::BDT;
    }
}
