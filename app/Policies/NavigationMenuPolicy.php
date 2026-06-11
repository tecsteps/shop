<?php

namespace App\Policies;

use App\Models\NavigationMenu;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class NavigationMenuPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwnerAdminOrStaff($user, $this->currentStoreId());
    }

    public function view(User $user, NavigationMenu $menu): bool
    {
        return $this->isOwnerAdminOrStaff($user, $menu->store_id);
    }

    public function update(User $user, NavigationMenu $menu): bool
    {
        return $this->isOwnerOrAdmin($user, $menu->store_id);
    }
}
