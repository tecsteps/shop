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
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    public function view(User $user, Fulfillment $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $fulfillment->order->store_id);
    }
}
