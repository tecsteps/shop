<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Page;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class PagePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, StoreUserRole::cases());
    }

    public function view(User $user, Page $page): bool
    {
        return $this->userHasModelStoreRole($user, $page, StoreUserRole::cases());
    }

    public function create(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->userHasModelStoreRole($user, $page, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->userHasModelStoreRole($user, $page, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
