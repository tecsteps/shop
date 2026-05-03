<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class PagePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    public function view(User $user, object $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($page));
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user);
    }

    public function update(User $user, object $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->storeIdForModel($page));
    }

    public function delete(User $user, object $page): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($page));
    }
}
