<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    /**
     * @param  \App\Models\Order  $order
     */
    public function create(User $user, $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
