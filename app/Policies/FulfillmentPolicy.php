<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user, $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    public function update(User $user, $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $fulfillment->order->store_id);
    }

    public function cancel(User $user, $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $fulfillment->order->store_id);
    }
}
