<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Collection;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class CollectionPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, StoreUserRole::cases());
    }

    public function view(User $user, Collection $collection): bool
    {
        return $this->userHasModelStoreRole($user, $collection, StoreUserRole::cases());
    }

    public function create(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Collection $collection): bool
    {
        return $this->userHasModelStoreRole($user, $collection, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function delete(User $user, Collection $collection): bool
    {
        return $this->userHasModelStoreRole($user, $collection, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
