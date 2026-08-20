<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class FulfillmentPolicy
{
    use ChecksStoreRole;

    public function create(User $user, Order $order): bool
    {
        return $this->userHasModelStoreRole($user, $order, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function view(User $user, Fulfillment $fulfillment): bool
    {
        return $this->userHasModelStoreRole($user, $fulfillment->loadMissing('order')->order, StoreUserRole::cases());
    }
}
