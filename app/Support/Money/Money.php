<?php

namespace App\Support\Money;

use App\Enums\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable amount of money: integer minor units (paisa/cents) plus a currency.
 * Never built from a float. Every operation that can produce fractions of a
 * minor unit rounds once, HalfUp, at the currency's scale.
 */
final readonly class Money implements JsonSerializable
{
    private function __construct(
        public int $minor,
        public Currency $currency,
    ) {}

    public static function ofMinor(int $minor, Currency|string $currency): self
    {
        return new self($minor, self::currency($currency));
    }

    /**
     * From a decimal string in major units, e.g. "1250.50". Rejects more decimal
     * places than the currency allows rather than silently rounding user input.
     */
    public static function of(string|int $amount, Currency|string $currency): self
    {
        $currency = self::currency($currency);

        try {
            $minor = BigDecimal::of($amount)
                ->toScale($currency->minorUnits(), RoundingMode::Unnecessary)
                ->getUnscaledValue()
                ->toInt();
        } catch (MathException $e) {
            throw new InvalidArgumentException("Invalid {$currency->value} amount: {$amount}", previous: $e);
        }

        return new self($minor, $currency);
    }

    public static function zero(Currency|string $currency): self
    {
        return new self(0, self::currency($currency));
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->checked(BigDecimal::of($this->minor)->plus($other->minor)), $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->checked(BigDecimal::of($this->minor)->minus($other->minor)), $this->currency);
    }

    /** Multiply by an exact factor given as int or decimal string, e.g. "1.15". */
    public function multipliedBy(string|int $factor, RoundingMode $rounding = RoundingMode::HalfUp): self
    {
        return $this->withMinor(BigDecimal::of($this->minor)->multipliedBy($factor), $rounding);
    }

    /** That percentage of this amount: Money::of('200', 'BDT')->percent('7.5') is ৳15.00. */
    public function percent(string|int $percent, RoundingMode $rounding = RoundingMode::HalfUp): self
    {
        $minor = BigDecimal::of($this->minor)->multipliedBy($percent)->dividedBy(100, 0, $rounding);

        return new self($this->checked($minor), $this->currency);
    }

    /**
     * Build an amount in this money's currency from an exact minor-unit value,
     * rounding once. Used by the converter so maths stays exact until the end.
     */
    public function withMinor(BigNumber $minor, RoundingMode $rounding = RoundingMode::HalfUp): self
    {
        return new self($this->checked($minor->toScale(0, $rounding)), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function isNegative(): bool
    {
        return $this->minor < 0;
    }

    public function isPositive(): bool
    {
        return $this->minor > 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->minor === $other->minor;
    }

    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->minor <=> $other->minor;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function isLessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /** Major units as a plain decimal string, e.g. "-1234.50". */
    public function toDecimal(): string
    {
        return (string) BigDecimal::ofUnscaledValue($this->minor, $this->currency->minorUnits());
    }

    /** Display form: "৳1,23,456.78", "$1,234.56", "-€5.00". */
    public function format(): string
    {
        $decimal = BigDecimal::ofUnscaledValue(abs($this->minor), $this->currency->minorUnits());
        [$whole, $fraction] = array_pad(explode('.', (string) $decimal), 2, '');

        $grouped = $this->currency->usesLakhGrouping()
            ? self::groupLakh($whole)
            : number_format((int) $whole, 0, '', ',');

        return ($this->isNegative() ? '-' : '')
            .$this->currency->symbol()
            .$grouped
            .($fraction === '' ? '' : '.'.$fraction);
    }

    public function jsonSerialize(): array
    {
        return ['amount' => $this->toDecimal(), 'minor' => $this->minor, 'currency' => $this->currency->value];
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatchException::between($this->currency, $other->currency);
        }
    }

    /** Convert to int, refusing to overflow PHP_INT_MAX instead of wrapping to a float. */
    private function checked(BigDecimal $minor): int
    {
        try {
            return $minor->toBigInteger()->toInt();
        } catch (MathException $e) {
            throw new InvalidArgumentException('Money amount out of range.', previous: $e);
        }
    }

    private static function currency(Currency|string $currency): Currency
    {
        return $currency instanceof Currency ? $currency : Currency::from(strtoupper($currency));
    }

    /** 1234567 → "12,34,567": last three digits, then groups of two. */
    private static function groupLakh(string $digits): string
    {
        if (strlen($digits) <= 3) {
            return $digits;
        }

        $head = substr($digits, 0, -3);
        $groups = str_split(strrev($head), 2);

        return strrev(implode(',', $groups)).','.substr($digits, -3);
    }
}
