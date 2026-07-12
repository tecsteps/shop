<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

final class CustomerPolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, null, ['owner', 'admin', 'staff', 'support']);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->hasRole($user, $customer, ['owner', 'admin', 'staff', 'support']);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->hasRole($user, $customer, ['owner', 'admin', 'staff']);
    }
}
