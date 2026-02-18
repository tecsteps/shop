<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class PagePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Page  $page
     */
    public function view(User $user, $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $page->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Page  $page
     */
    public function update(User $user, $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $page->store_id);
    }

    /**
     * @param  \App\Models\Page  $page
     */
    public function delete(User $user, $page): bool
    {
        return $this->isOwnerOrAdmin($user, $page->store_id);
    }
}
