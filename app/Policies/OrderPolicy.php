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
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->isAnyRole($user, $order->store_id);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
