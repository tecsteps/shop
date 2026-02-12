<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    private function getUserRole(User $user): ?StoreUserRole
    {
        if (! app()->bound('current_store')) {
            return null;
        }

        return $user->roleForStore(app('current_store'));
    }

    public function viewAny(User $user): bool
    {
        $role = $this->getUserRole($user);

        return in_array($role, [
            StoreUserRole::Owner,
            StoreUserRole::Admin,
            StoreUserRole::Staff,
            StoreUserRole::Support,
        ]);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        $role = $this->getUserRole($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Customer $customer): bool
    {
        $role = $this->getUserRole($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
