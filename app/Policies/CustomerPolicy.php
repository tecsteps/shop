<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class CustomerPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, StoreUserRole::cases());
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->userHasModelStoreRole($user, $customer, StoreUserRole::cases());
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->userHasModelStoreRole($user, $customer, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }
}
