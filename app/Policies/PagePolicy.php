<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Page;
use App\Models\User;
use App\Policies\Concerns\ChecksStoreRoles;

class PagePolicy
{
    use ChecksStoreRoles;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function view(User $user, Page $page): bool
    {
        return $this->hasRole($user, $page->store, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyRole($user, [StoreUserRole::Owner, StoreUserRole::Admin, StoreUserRole::Staff]);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->view($user, $page);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->hasRole($user, $page->store, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }
}
