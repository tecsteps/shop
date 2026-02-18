<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Order  $order
     */
    public function view(User $user, $order): bool
    {
        return $this->isAnyRole($user, $order->store_id);
    }

    /**
     * @param  \App\Models\Order  $order
     */
    public function update(User $user, $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    /**
     * @param  \App\Models\Order  $order
     */
    public function cancel(User $user, $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }

    /**
     * @param  \App\Models\Order  $order
     */
    public function createFulfillment(User $user, $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    /**
     * @param  \App\Models\Order  $order
     */
    public function createRefund(User $user, $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
