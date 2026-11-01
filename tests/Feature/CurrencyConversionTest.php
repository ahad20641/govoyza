<?php

namespace Tests\Feature;

use App\Enums\Currency;
use App\Models\ExchangeRate;
use App\Services\Money\CurrencyConverter;
use App\Support\Money\Money;
use RuntimeException;
use Tests\TestCase;

class CurrencyConversionTest extends TestCase
{
    private CurrencyConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = app(CurrencyConverter::class);
    }

    private function rate(string $base, string $quote, string $rate, string $buffer = '0', ?string $effective = null): void
    {
        ExchangeRate::create([
            'base' => $base,
            'quote' => $quote,
            'rate' => $rate,
            'buffer_pct' => $buffer,
            'effective_at' => $effective ? now()->modify($effective) : now()->subMinute(),
        ]);
    }

    public function test_same_currency_is_returned_unchanged(): void
    {
        $money = Money::of('10', 'BDT');

        $this->assertSame($money, $this->converter->convert($money, Currency::BDT));
    }

    public function test_converts_at_the_direct_rate(): void
    {
        $this->rate('USD', 'BDT', '122.50');

        $bdt = $this->converter->convert(Money::of('10', 'USD'), Currency::BDT);

        $this->assertTrue($bdt->equals(Money::of('1225.00', 'BDT')));
    }

    public function test_buffer_increases_the_converted_amount(): void
    {
        $this->rate('USD', 'BDT', '122.50', buffer: '2');

        // 10 × 122.50 × 1.02 = 1249.50
        $this->assertSame(124950, $this->converter->convert(Money::of('10', 'USD'), Currency::BDT)->minor);
        $this->assertSame(122500, $this->converter->convert(Money::of('10', 'USD'), Currency::BDT, withBuffer: false)->minor);
    }

    public function test_uses_the_inverse_rate_when_no_direct_pair_exists(): void
    {
        $this->rate('USD', 'BDT', '122.50');

        // 1000 / 122.50 = 8.1632… → 8.16
        $this->assertSame(816, $this->converter->convert(Money::of('1000', 'BDT'), Currency::USD)->minor);
    }

    public function test_conversion_rounds_half_up_once(): void
    {
        $this->rate('EUR', 'USD', '1.005');

        // 1.00 × 1.005 = 1.005 → 1.01
        $this->assertSame(101, $this->converter->convert(Money::of('1', 'EUR'), Currency::USD)->minor);
    }

    public function test_uses_the_latest_rate_in_effect_and_ignores_future_rates(): void
    {
        $this->rate('USD', 'BDT', '120', effective: '-1 day');
        $this->rate('USD', 'BDT', '122', effective: '-1 hour');
        $this->rate('USD', 'BDT', '125', effective: '+1 day');

        $this->assertSame(12200, $this->converter->convert(Money::of('1', 'USD'), Currency::BDT)->minor);
        $this->assertSame(12000, $this->converter->convert(Money::of('1', 'USD'), Currency::BDT, at: now()->subHours(2))->minor);
    }

    public function test_missing_rate_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->converter->convert(Money::of('1', 'EUR'), Currency::BDT);
    }
}
