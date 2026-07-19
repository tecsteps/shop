<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    /**
     * Creating a refund is authorized against the parent order, since
     * the refund does not exist yet.
     */
    public function create(User $user, Order $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
