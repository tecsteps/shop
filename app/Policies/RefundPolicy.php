<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class RefundPolicy
{
    use ChecksStoreRole;

    public function create(User $user, Order $order): bool
    {
        return $this->userHasModelStoreRole($user, $order, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function view(User $user, Refund $refund): bool
    {
        return $this->userHasModelStoreRole($user, $refund, StoreUserRole::cases());
    }
}
