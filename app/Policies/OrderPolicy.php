<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Authorization for orders: only the owner may view or modify their own order.
 */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }
}
