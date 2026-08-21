<?php

namespace App\Policies;

use App\Enums\StoreUserRole;
use App\Models\Theme;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class ThemePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, StoreUserRole::cases());
    }

    public function view(User $user, Theme $theme): bool
    {
        return $this->userHasModelStoreRole($user, $theme, StoreUserRole::cases());
    }

    public function create(User $user): bool
    {
        return $this->userHasCurrentStoreRole($user, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function update(User $user, Theme $theme): bool
    {
        return $this->userHasModelStoreRole($user, $theme, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function delete(User $user, Theme $theme): bool
    {
        return $this->userHasModelStoreRole($user, $theme, [StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function publish(User $user, Theme $theme): bool
    {
        return $this->update($user, $theme);
    }
}
