<?php

namespace Tests\Unit;

use App\Enums\Currency;
use App\Support\Money\CurrencyMismatchException;
use App\Support\Money\Money;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_parses_decimal_strings_and_ints_into_minor_units(): void
    {
        $this->assertSame(125050, Money::of('1250.50', 'BDT')->minor);
        $this->assertSame(10000, Money::of(100, Currency::USD)->minor);
        $this->assertSame(-5, Money::of('-0.05', 'eur')->minor);
        $this->assertSame(Currency::EUR, Money::of('1', 'eur')->currency);
    }

    public function test_it_rejects_more_decimals_than_the_currency_allows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of('10.005', 'USD');
    }

    public function test_it_rejects_non_numeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::of('ten', 'USD');
    }

    public function test_add_and_subtract(): void
    {
        $sum = Money::of('10.25', 'BDT')->plus(Money::of('5.80', 'BDT'));
        $diff = Money::of('5.00', 'BDT')->minus(Money::of('7.50', 'BDT'));

        $this->assertSame(1605, $sum->minor);
        $this->assertSame(-250, $diff->minor);
        $this->assertTrue($diff->isNegative());
    }

    public function test_operations_are_immutable(): void
    {
        $price = Money::of('100', 'USD');
        $price->plus(Money::of('1', 'USD'));
        $price->percent(50);

        $this->assertSame(10000, $price->minor);
    }

    public function test_mixing_currencies_throws(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::of('1', 'USD')->plus(Money::of('1', 'BDT'));
    }

    public function test_comparing_mixed_currencies_throws(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::of('1', 'USD')->isGreaterThan(Money::of('1', 'EUR'));
    }

    public function test_multiply_by_exact_factor(): void
    {
        $this->assertSame(11500, Money::of('100.00', 'BDT')->multipliedBy('1.15')->minor);
        $this->assertSame(99, Money::of('0.33', 'USD')->multipliedBy(3)->minor);
    }

    public function test_multiply_by_percent(): void
    {
        $this->assertSame(1500, Money::of('200', 'BDT')->percent('7.5')->minor);
        // 999.99 × 15% = 149.9985 → 150.00
        $this->assertSame(15000, Money::of('999.99', 'BDT')->percent(15)->minor);
        // Long fractional percentages don't throw; rounded once at the end.
        $this->assertSame(3333, Money::of('100', 'USD')->percent('33.333333333333')->minor);
    }

    public function test_rounding_is_half_up_away_from_zero(): void
    {
        // 0.10 × 25% = 0.025 → 0.03
        $this->assertSame(3, Money::of('0.10', 'USD')->percent(25)->minor);
        // -0.10 × 25% = -0.025 → -0.03
        $this->assertSame(-3, Money::of('-0.10', 'USD')->percent(25)->minor);
        // 0.01 × 0.5 = 0.005 → 0.01
        $this->assertSame(1, Money::of('0.01', 'USD')->multipliedBy('0.5')->minor);
        // 0.024 → 0.02 (below the midpoint)
        $this->assertSame(2, Money::of('0.08', 'USD')->multipliedBy('0.3')->minor);
    }

    public function test_rounding_mode_can_be_overridden(): void
    {
        $this->assertSame(0, Money::of('0.01', 'USD')->multipliedBy('0.5', RoundingMode::HalfEven)->minor);
        $this->assertSame(2, Money::of('0.03', 'USD')->multipliedBy('0.5', RoundingMode::HalfEven)->minor);
    }

    public function test_overflow_throws_instead_of_becoming_a_float(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::ofMinor(PHP_INT_MAX, 'USD')->plus(Money::ofMinor(1, 'USD'));
    }

    public function test_comparisons(): void
    {
        $a = Money::of('10', 'BDT');
        $b = Money::of('10.01', 'BDT');

        $this->assertTrue($b->isGreaterThan($a));
        $this->assertTrue($a->isLessThan($b));
        $this->assertTrue($a->equals(Money::ofMinor(1000, 'BDT')));
        $this->assertFalse($a->equals(Money::ofMinor(1000, 'USD')));
        $this->assertTrue(Money::zero('EUR')->isZero());
    }

    public function test_decimal_string(): void
    {
        $this->assertSame('-1234.50', Money::ofMinor(-123450, 'BDT')->toDecimal());
        $this->assertSame('0.05', Money::ofMinor(5, 'USD')->toDecimal());
    }

    public function test_bdt_formats_with_lakh_grouping(): void
    {
        $this->assertSame('৳999.00', Money::of('999', 'BDT')->format());
        $this->assertSame('৳1,000.00', Money::of('1000', 'BDT')->format());
        $this->assertSame('৳1,23,456.78', Money::of('123456.78', 'BDT')->format());
        $this->assertSame('৳1,23,45,678.90', Money::of('12345678.90', 'BDT')->format());
    }

    public function test_usd_and_eur_format_with_western_grouping(): void
    {
        $this->assertSame('$1,234.56', Money::of('1234.56', 'USD')->format());
        $this->assertSame('€1,234,567.00', Money::of('1234567', 'EUR')->format());
        $this->assertSame('-€5.00', Money::of('-5', 'EUR')->format());
        $this->assertSame('$0.07', (string) Money::ofMinor(7, 'USD'));
    }

    public function test_json_uses_decimal_string_not_float(): void
    {
        $this->assertSame(
            '{"amount":"1250.50","minor":125050,"currency":"BDT"}',
            json_encode(Money::of('1250.50', 'BDT')),
        );
    }
}
