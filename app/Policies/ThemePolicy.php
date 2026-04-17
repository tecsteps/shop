<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    protected function getStoreId(): int
    {
        return app('current_store')->id;
    }

    public function viewAny(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->getStoreId());
    }

    public function view(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->getStoreId());
    }

    public function update(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    public function delete(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    public function publish(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }
}
