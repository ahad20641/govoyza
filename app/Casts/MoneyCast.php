<?php

namespace App\Casts;

use App\Support\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Maps a Money attribute onto a BIGINT minor-units column plus a CHAR(3)
 * currency column, e.g. 'total' => MoneyCast::class.':total_minor,currency'.
 * Defaults: "<key>_minor" and "currency".
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(
        private ?string $amountColumn = null,
        private string $currencyColumn = 'currency',
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $minor = $attributes[$this->amountColumn($key)] ?? null;
        $currency = $attributes[$this->currencyColumn] ?? null;

        if ($minor === null || $currency === null) {
            return null;
        }

        return Money::ofMinor((int) $minor, $currency);
    }

    /** @return array<string, int|string|null> */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$this->amountColumn($key) => null];
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException("[{$key}] must be a Money instance, ".get_debug_type($value).' given.');
        }

        return [
            $this->amountColumn($key) => $value->minor,
            $this->currencyColumn => $value->currency->value,
        ];
    }

    private function amountColumn(string $key): string
    {
        return $this->amountColumn ?? "{$key}_minor";
    }
}
