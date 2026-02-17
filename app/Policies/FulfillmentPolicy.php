<?php

namespace App\Policies;

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user, Order $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId($order));
    }

    public function update(User $user, Fulfillment $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $fulfillment->order->store_id);
    }

    public function cancel(User $user, Fulfillment $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $fulfillment->order->store_id);
    }
}
