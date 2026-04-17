<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, object $order): bool
    {
        return $this->isAnyRole($user, (int) $order->store_id);
    }

    public function update(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $order->store_id);
    }

    public function cancel(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $order->store_id);
    }

    public function createFulfillment(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $order->store_id);
    }

    public function createRefund(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $order->store_id);
    }
}
