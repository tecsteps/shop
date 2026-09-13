<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Refund $refund): bool
    {
        return $this->isAnyRole($user, $refund->order->store_id);
    }

    public function create(User $user, Order $order): bool
    {
        return $this->isOwnerOrAdmin($user, $order->store_id);
    }
}
