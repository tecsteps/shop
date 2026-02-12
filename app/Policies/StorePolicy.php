<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;

class StorePolicy
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

    public function view(User $user, Store $store): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        $role = $this->getUserRole($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function update(User $user, Store $store): bool
    {
        $role = $this->getUserRole($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function delete(User $user, Store $store): bool
    {
        $role = $this->getUserRole($user);

        return $role === StoreUserRole::Owner;
    }
}
