<?php

namespace App\Policies;

use App\Models\TaxSetting;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class TaxSettingPolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }

    public function view(User $user, TaxSetting $settings): bool
    {
        return $this->isOwnerOrAdmin($user, $settings->store_id);
    }

    public function update(User $user, TaxSetting $settings): bool
    {
        return $this->isOwnerOrAdmin($user, $settings->store_id);
    }
}
