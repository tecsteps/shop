<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $this->getRole($user);

        return in_array($role, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function view(User $user, Collection $collection): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Collection $collection): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $this->viewAny($user);
    }

    private function getRole(User $user): ?StoreUserRole
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store ? $user->roleForStore($store) : null;
    }
}
