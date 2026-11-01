<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Countries, nationalities and districts: public to read, catalogue staff to change. */
class ReferenceDataPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Model $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasPermission('catalog.manage');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermission('catalog.manage');
    }
}
