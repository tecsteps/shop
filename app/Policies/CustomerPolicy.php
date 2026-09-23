<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Store;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['owner', 'admin', 'staff', 'support']);
    }

    public function view(User $user, Customer $customer): bool
    {
        return (int) $customer->store_id === (int) (app()->bound('current_store') ? app('current_store')->id : 0)
            && $this->hasRole($user, ['owner', 'admin', 'staff', 'support']);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer) && $this->hasRole($user, ['owner', 'admin', 'staff']);
    }

    private function hasRole(User $user, array $roles): bool
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store instanceof Store && in_array($user->roleForStore($store), $roles, true);
    }
}
