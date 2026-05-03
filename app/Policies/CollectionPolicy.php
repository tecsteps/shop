<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Collection;
use App\Models\Store;
use App\Models\User;

class CollectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function view(User $user, Collection $collection): bool
    {
        return $this->hasRole($user, $collection->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Collection $collection): bool
    {
        return $this->hasRole($user, $collection->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $this->hasRole($user, $collection->store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function restore(User $user, Collection $collection): bool
    {
        return $this->update($user, $collection);
    }

    public function forceDelete(User $user, Collection $collection): bool
    {
        return $this->delete($user, $collection);
    }

    /**
     * @param  list<StoreUserRole>  $roles
     */
    private function hasRole(User $user, Store $store, array $roles): bool
    {
        return in_array($user->roleForStore($store), $roles, true);
    }

    /**
     * @param  list<StoreUserRole>  $roles
     */
    private function hasAnyRole(User $user, array $roles): bool
    {
        if (! app()->bound('current_store')) {
            return false;
        }

        return $this->hasRole($user, app('current_store'), $roles);
    }
}
