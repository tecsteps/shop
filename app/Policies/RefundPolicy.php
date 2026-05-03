<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Refund;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class RefundPolicy
{
    use ChecksStoreRoles;

    public function view(User $user, Refund $refund): bool
    {
        return $this->hasRole($user, $refund->order->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Support]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
