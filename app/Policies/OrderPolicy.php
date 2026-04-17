<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;
use Illuminate\Database\Eloquent\Model;

class OrderPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Model $order): bool
    {
        return $this->isAnyRole($user, $order->store_id);
    }

    public function update(User $user, Model $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    public function cancel(User $user, Model $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }

    public function createFulfillment(User $user, Model $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    public function createRefund(User $user, Model $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
