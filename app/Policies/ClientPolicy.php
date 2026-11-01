<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('clients.manage');
    }

    public function view(User $user, Client $client): bool
    {
        return $client->user_id === $user->id || $user->hasPermission('clients.manage');
    }

    public function update(User $user, Client $client): bool
    {
        return $client->user_id === $user->id || $user->hasPermission('clients.manage');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->hasPermission('clients.manage');
    }
}
