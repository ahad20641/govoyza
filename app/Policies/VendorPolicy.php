<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

/** Vendors are suppliers: only Operations (and Owner) see them, plus the vendor's own login. */
class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('partners.manage');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $vendor->user_id === $user->id || $user->hasPermission('partners.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('partners.manage');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('partners.manage');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('partners.manage');
    }
}
