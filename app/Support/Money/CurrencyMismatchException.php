<?php

namespace App\Support\Money;

use App\Enums\Currency;
use InvalidArgumentException;

final class CurrencyMismatchException extends InvalidArgumentException
{
    public static function between(Currency $a, Currency $b): self
    {
        return new self("Cannot combine {$a->value} with {$b->value}; convert first.");
    }
}
