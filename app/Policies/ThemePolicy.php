<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Theme  $theme
     */
    public function view(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user, $this->resolveStoreId());
    }

    /**
     * @param  \App\Models\Theme  $theme
     */
    public function update(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    /**
     * @param  \App\Models\Theme  $theme
     */
    public function delete(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }

    /**
     * @param  \App\Models\Theme  $theme
     */
    public function publish(User $user, $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $theme->store_id);
    }
}
