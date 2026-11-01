<?php

namespace App\Services\Money;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use App\Support\Money\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DateTimeInterface;
use RuntimeException;

/**
 * Converts Money between currencies at the admin-set rate in effect. The buffer
 * is a safety margin that always increases the converted amount; pass
 * withBuffer: false for reporting at the plain rate.
 */
class CurrencyConverter
{
    /** Scale for the intermediate amount, so the final HalfUp rounding is the only one. */
    private const PRECISION = 20;

    public function convert(Money $money, Currency $to, ?DateTimeInterface $at = null, bool $withBuffer = true): Money
    {
        if ($money->currency === $to) {
            return $money;
        }

        [$rate, $bufferPct] = $this->rateFor($money->currency, $to, $at ?? now());

        $amount = BigDecimal::ofUnscaledValue($money->minor, $money->currency->minorUnits())->multipliedBy($rate);

        if ($withBuffer && ! $bufferPct->isZero()) {
            $amount = $amount->multipliedBy($bufferPct->dividedBy(100, self::PRECISION)->plus(1));
        }

        // Major units in the target currency → minor units, rounded once.
        $minor = $amount->multipliedBy(BigDecimal::one()->withPointMovedRight($to->minorUnits()));

        return Money::zero($to)->withMinor($minor, RoundingMode::HalfUp);
    }

    /** @return array{BigDecimal, BigDecimal} rate (from → to) and buffer % */
    private function rateFor(Currency $from, Currency $to, DateTimeInterface $at): array
    {
        $direct = ExchangeRate::query()->inEffect($from, $to, $at)->first();

        if ($direct) {
            return [BigDecimal::of($direct->rate), BigDecimal::of($direct->buffer_pct)];
        }

        $inverse = ExchangeRate::query()->inEffect($to, $from, $at)->first();

        if ($inverse) {
            return [
                BigDecimal::one()->dividedBy($inverse->rate, self::PRECISION, RoundingMode::HalfUp),
                BigDecimal::of($inverse->buffer_pct),
            ];
        }

        throw new RuntimeException("No exchange rate in effect for {$from->value} → {$to->value}.");
    }
}
