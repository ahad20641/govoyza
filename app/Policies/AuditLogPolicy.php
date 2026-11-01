<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/** Read-only for everyone; the model itself refuses updates and deletes. */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function update(User $user, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $log): bool
    {
        return false;
    }
}
