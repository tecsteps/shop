<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class PagePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function view(User $user, object $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $page->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, object $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, (int) $page->store_id);
    }

    public function delete(User $user, object $page): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $page->store_id);
    }
}
