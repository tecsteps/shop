<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user, object $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $order->store_id);
    }

    public function update(User $user, object $fulfillment): bool
    {
        $storeId = $fulfillment->order->store_id ?? $fulfillment->store_id ?? null;

        if ($storeId === null) {
            return false;
        }

        return $this->isOwnerAdminOrStaff($user, (int) $storeId);
    }

    public function cancel(User $user, object $fulfillment): bool
    {
        $storeId = $fulfillment->order->store_id ?? $fulfillment->store_id ?? null;

        if ($storeId === null) {
            return false;
        }

        return $this->isOwnerAdminOrStaff($user, (int) $storeId);
    }
}
