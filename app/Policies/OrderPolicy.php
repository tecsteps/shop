<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class OrderPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, StoreUserRole::cases());
    }

    public function view(User $user, Order $order): bool
    {
        return $this->userHasModelStoreRole($user, $order, StoreUserRole::cases());
    }

    public function update(User $user, Order $order): bool
    {
        return $this->userHasModelStoreRole($user, $order, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->userHasModelStoreRole($user, $order, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function createFulfillment(User $user, Order $order): bool
    {
        return $this->userHasModelStoreRole($user, $order, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function createRefund(User $user, Order $order): bool
    {
        return $this->userHasModelStoreRole($user, $order, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
