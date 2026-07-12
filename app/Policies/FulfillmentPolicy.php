<?php

namespace App\Policies;

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class FulfillmentPolicy
{
    use ChecksStoreRoles;

    public function create(User $user, Order $order): bool
    {
        return $this->hasRole($user, $order, ['owner', 'admin', 'staff']);
    }

    public function update(User $user, Fulfillment $fulfillment): bool
    {
        return $this->hasRole($user, $fulfillment, ['owner', 'admin', 'staff']);
    }

    public function cancel(User $user, Fulfillment $fulfillment): bool
    {
        return $this->update($user, $fulfillment);
    }
}
