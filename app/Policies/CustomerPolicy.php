<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isAnyRole($user, $storeId);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->isAnyRole($user, $customer->store_id);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->isOwnerAdminOrStaff($user, $customer->store_id);
    }
}
