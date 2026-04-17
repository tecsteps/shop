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
        return $this->isAnyRole($user, $this->resolveStoreId());
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
