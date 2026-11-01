<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

/** Suppliers: Operations (and Owner) manage them; a partner's own users see their own record. */
class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('partners.manage');
    }

    public function view(User $user, Partner $partner): bool
    {
        return $user->partner_id === $partner->id || $user->hasPermission('partners.manage');
    }

    public function invite(User $user): bool
    {
        return $user->hasPermission('partners.manage');
    }

    /** Approve, suspend, reactivate. */
    public function approve(User $user, Partner $partner): bool
    {
        return $user->hasPermission('partners.manage');
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->hasPermission('partners.manage');
    }

    /** Partner history feeds payables and audits; suspend instead. */
    public function delete(User $user, Partner $partner): bool
    {
        return false;
    }
}
