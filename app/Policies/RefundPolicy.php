<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function create(User $user, Order $order): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId($order));
    }
}
