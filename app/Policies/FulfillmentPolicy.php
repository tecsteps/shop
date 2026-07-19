<?php

namespace App\Policies;

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    /**
     * Creating a fulfillment is authorized against the parent order,
     * since the fulfillment does not exist yet.
     */
    public function create(User $user, Order $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
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
