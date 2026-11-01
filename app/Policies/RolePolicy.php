<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/** Roles come from RoleSlug and permissions from config/permissions.php, so neither is edited in the app. */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('staff.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission('staff.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return false;
    }

    public function delete(User $user, Role $role): bool
    {
        return false;
    }
}
