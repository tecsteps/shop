<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function view(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($theme));
    }

    public function create(User $user): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    public function update(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($theme));
    }

    public function publish(User $user, object $theme): bool
    {
        return $this->update($user, $theme);
    }

    public function delete(User $user, object $theme): bool
    {
        return $this->isOwnerOrAdmin($user, $this->storeIdForModel($theme));
    }
}
