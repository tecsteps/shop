<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;
use Illuminate\Database\Eloquent\Model;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user, Model $order): bool
    {
        return $this->isOwnerAdminOrStaff($user, $order->store_id);
    }

    public function update(User $user, Model $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $fulfillment->order->store_id);
    }

    public function cancel(User $user, Model $fulfillment): bool
    {
        return $this->isOwnerAdminOrStaff($user, $fulfillment->order->store_id);
    }
}
