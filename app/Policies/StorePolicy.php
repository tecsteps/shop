<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class StorePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $user->stores()->exists();
    }

    public function view(User $user, Store $store): bool
    {
        return $this->userHasModelStoreRole($user, $store, StoreUserRole::cases());
    }

    public function create(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function update(User $user, Store $store): bool
    {
        return $this->userHasModelStoreRole($user, $store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->userHasModelStoreRole($user, $store, [StoreUserRole::Owner]);
    }

    public function restore(User $user, Store $store): bool
    {
        return $this->delete($user, $store);
    }

    public function forceDelete(User $user, Store $store): bool
    {
        return $this->delete($user, $store);
    }
}
