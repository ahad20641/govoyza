<?php

namespace App\Policies;

use App\Models\PartnerDocument;
use App\Models\User;

class PartnerDocumentPolicy
{
    public function view(User $user, PartnerDocument $document): bool
    {
        return $user->partner_id === $document->partner_id || $user->hasPermission('partners.manage');
    }

    public function delete(User $user, PartnerDocument $document): bool
    {
        return $user->hasPermission('partners.manage');
    }
}
