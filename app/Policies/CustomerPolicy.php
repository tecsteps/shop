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
        return $this->isAnyRole($user);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->isAnyRole($user, $this->storeIdForModel($customer));
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($customer));
    }
}
