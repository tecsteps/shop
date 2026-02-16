<?php

namespace App\Policies;

use App\Models\Fulfillment;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, Fulfillment $fulfillment): bool
    {
        /** @var int $storeId */
        $storeId = $fulfillment->order?->store_id;

        return $this->isOwnerAdminOrStaff($user, $storeId);
    }
}
