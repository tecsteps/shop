<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class PagePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function view(User $user, Page $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $page->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerAdminOrStaff($user, $storeId);
    }

    public function update(User $user, Page $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $page->store_id);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->isOwnerOrAdmin($user, $page->store_id);
    }
}
