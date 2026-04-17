<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }

    public function view(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->resolveCurrentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }

    public function update(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    public function delete(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }

    public function publish(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, (int) $theme->store_id);
    }
}
