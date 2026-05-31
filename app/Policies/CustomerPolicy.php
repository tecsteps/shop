<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    /**
     * List customers (any role).
     */
    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isAnyRole($user, $storeId);
    }

    /**
     * View a customer (any role).
     */
    public function view(User $user, Customer $customer): bool
    {
        return $this->isAnyRole($user, $customer->store_id);
    }

    /**
     * Update a customer (Owner, Admin, or Staff).
     */
    public function update(User $user, Customer $customer): bool
    {
        return $this->isOwnerAdminOrStaff($user, $customer->store_id);
    }
}
