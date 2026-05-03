<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isAnyRole($user);
    }

    public function view(User $user, object $order): bool
    {
        return $this->isAnyRole($user, $this->storeIdForModel($order));
    }

    public function update(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($order));
    }

    public function cancel(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($order));
    }

    public function createFulfillment(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($order));
    }

    public function createRefund(User $user, object $order): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($order));
    }
}
