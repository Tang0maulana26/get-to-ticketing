<?php

namespace App\Policies;

use App\Models\{Event, User};

class EventPolicy
{
    public function viewAny(User $user): bool { return $user->role === 'admin'; }
    public function create(User $user): bool { return $user->role === 'admin'; }
    public function view(User $user, Event $event): bool { return $user->role === 'admin'; }
    public function update(User $user, Event $event): bool { return $user->role === 'admin'; }
    public function delete(User $user, Event $event): bool { return $user->role === 'admin'; }
}
