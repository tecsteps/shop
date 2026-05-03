<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class OrderPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function fulfill(User $user, Order $order): bool
    {
        return $this->update($user, $order);
    }

    public function refund(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order->store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
