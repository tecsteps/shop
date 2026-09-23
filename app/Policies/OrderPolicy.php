<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin', 'staff', 'support']);
    }

    public function view(User $user, Order $order): bool
    {
        return $order->store_id === (int) (app()->bound('current_store') ? app('current_store')->id : 0)
            && $this->hasRole($user, ['owner', 'admin', 'staff', 'support']);
    }

    public function update(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    public function processRefund(User $user, Order $order): bool
    {
        return $this->view($user, $order) && $this->hasRole($user, ['owner', 'admin']);
    }

    public function createFulfillment(User $user, Order $order): bool
    {
        return $this->update($user, $order);
    }

    private function hasRole(User $user, array $roles): bool
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store instanceof Store && in_array($user->roleForStore($store), $roles, true);
    }
}
