<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId());
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isAnyRole($user, $order->store_id);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }
}
