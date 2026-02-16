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
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerOrAdmin($user, $storeId);
    }

    public function view(User $user, Theme $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveStoreId();

        return $storeId && $this->isOwnerOrAdmin($user, $storeId);
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
