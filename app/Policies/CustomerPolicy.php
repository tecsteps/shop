<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, object $customer): bool
    {
        return $this->isAnyRole($user, (int) $customer->store_id);
    }

    public function update(User $user, object $customer): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $customer->store_id);
    }
}
