<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class OrderPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin', 'staff', 'support']);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order, ['owner', 'admin', 'staff', 'support']);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order, ['owner', 'admin', 'staff']);
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order, ['owner', 'admin']);
    }

    public function refund(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order, ['owner', 'admin']);
    }

    public function fulfill(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order, ['owner', 'admin', 'staff']);
    }

    public function createRefund(User $user, Order $order): bool
    {
        return $this->refund($user, $order);
    }

    public function createFulfillment(User $user, Order $order): bool
    {
        return $this->fulfill($user, $order);
    }
}
