<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    protected function getStoreId(): int
    {
        return app('current_store')->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user, $this->getStoreId());
    }

    public function view(User $user, $order): bool
    {
        return $this->isAnyRole($user, $order->store_id);
    }

    public function update(User $user, $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    public function cancel(User $user, $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }

    public function createFulfillment(User $user, $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    public function createRefund(User $user, $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
