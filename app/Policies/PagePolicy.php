<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class PagePolicy
{
    use ChecksStoreRole;

    protected function getStoreId(): int
    {
        return app('current_store')->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->getStoreId());
    }

    public function view(User $user, $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $page->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->getStoreId());
    }

    public function update(User $user, $page): bool
    {
        return $this->isOwnerAdminOrStaff($user, $page->store_id);
    }

    public function delete(User $user, $page): bool
    {
        return $this->isOwnerOrAdmin($user, $page->store_id);
    }
}
