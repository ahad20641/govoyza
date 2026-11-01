<?php

namespace App\Policies;

use App\Models\User;

/** Who may see or change a user account. Owner passes via Gate::before. */
class UserPolicy
{
    /** user type => permission needed to manage users of that type */
    private const PERMISSIONS = [
        'customer' => 'clients.manage',
        'agent_user' => 'agencies.manage',
        'partner_user' => 'vendors.manage',
        'admin' => 'staff.manage',
    ];

    public function viewAny(User $user): bool
    {
        return $user->isStaff() && collect(self::PERMISSIONS)->contains(fn (string $p) => $user->hasPermission($p));
    }

    public function view(User $user, User $target): bool
    {
        return $user->is($target) || $this->manages($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('staff.manage');
    }

    public function update(User $user, User $target): bool
    {
        return $user->is($target) || $this->manages($user, $target);
    }

    /** Role, type and status changes: admin-only, and never on yourself. */
    public function changeRole(User $user, User $target): bool
    {
        return ! $user->is($target) && $this->manages($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return ! $user->is($target) && $this->manages($user, $target);
    }

    private function manages(User $user, User $target): bool
    {
        return $user->isStaff()
            && $target->type !== null
            && $user->hasPermission(self::PERMISSIONS[$target->type->value]);
    }
}
