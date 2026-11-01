<?php

namespace App\Policies;

use App\Models\ExchangeRate;
use App\Models\User;

/** Rates are history: a change is a new row, so there is no update or delete. */
class ExchangeRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('exchange_rates.manage');
    }

    public function view(User $user, ExchangeRate $rate): bool
    {
        return $user->hasPermission('exchange_rates.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('exchange_rates.manage');
    }

    public function update(User $user, ExchangeRate $rate): bool
    {
        return false;
    }

    public function delete(User $user, ExchangeRate $rate): bool
    {
        return false;
    }
}
