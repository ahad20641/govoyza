<?php

namespace App\Policies;

use App\Models\AgencyDocument;
use App\Models\User;

class AgencyDocumentPolicy
{
    public function view(User $user, AgencyDocument $document): bool
    {
        return $document->agency?->user_id === $user->id || $user->hasPermission('agencies.manage');
    }

    public function review(User $user, AgencyDocument $document): bool
    {
        return $user->hasPermission('agencies.manage');
    }

    public function delete(User $user, AgencyDocument $document): bool
    {
        return $user->hasPermission('agencies.manage');
    }
}
