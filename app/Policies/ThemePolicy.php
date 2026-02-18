<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId());
    }

    public function view(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId());
    }

    public function update(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    public function delete(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }
}
