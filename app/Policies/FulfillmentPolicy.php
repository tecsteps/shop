<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Fulfillment;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class FulfillmentPolicy
{
    use ChecksStoreRoles;

    public function view(User $user, Fulfillment $fulfillment): bool
    {
        return $this->hasRole($user, $fulfillment->order->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff, StoreUserRole::Support]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Fulfillment $fulfillment): bool
    {
        return $this->hasRole($user, $fulfillment->order->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }
}
