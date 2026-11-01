<?php

namespace App\Policies;

use App\Models\Agency;
use App\Models\User;

class AgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('agencies.manage');
    }

    public function view(User $user, Agency $agency): bool
    {
        return $agency->user_id === $user->id || $user->hasPermission('agencies.manage');
    }

    /** Profile fields only; status and limits go through approve(). */
    public function update(User $user, Agency $agency): bool
    {
        return $agency->user_id === $user->id || $user->hasPermission('agencies.manage');
    }

    /** Approve, reject, suspend, and set credit/markup limits. */
    public function approve(User $user, Agency $agency): bool
    {
        return $user->hasPermission('agencies.manage');
    }

    public function delete(User $user, Agency $agency): bool
    {
        return $user->hasPermission('agencies.manage');
    }
}
