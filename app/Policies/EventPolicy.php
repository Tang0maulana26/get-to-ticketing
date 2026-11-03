<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Authorization for admin event management: only admins may view, create, update or delete.
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }
}
