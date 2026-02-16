<?php

namespace App\Policies;

use App\Models\StoreSettings;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class StoreSettingsPolicy
{
    use ChecksStoreRole;

    public function view(User $user, StoreSettings $settings): bool
    {
        return $this->isOwnerOrAdmin($user, $settings->store_id);
    }

    public function update(User $user, StoreSettings $settings): bool
    {
        return $this->isOwnerOrAdmin($user, $settings->store_id);
    }
}
