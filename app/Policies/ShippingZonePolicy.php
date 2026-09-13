<?php

namespace App\Policies;

use App\Models\ShippingZone;
use App\Models\User;
use App\Traits\ChecksStoreRole;

class ShippingZonePolicy
{
    use ChecksStoreRole;

    public function viewAny(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }

    public function view(User $user, ShippingZone $zone): bool
    {
        return $this->isOwnerOrAdmin($user, $zone->store_id);
    }

    public function create(User $user): bool
    {
        $storeId = $this->currentStoreId();

        return $storeId !== null && $this->isOwnerOrAdmin($user, $storeId);
    }

    public function update(User $user, ShippingZone $zone): bool
    {
        return $this->isOwnerOrAdmin($user, $zone->store_id);
    }

    public function delete(User $user, ShippingZone $zone): bool
    {
        return $this->isOwnerOrAdmin($user, $zone->store_id);
    }
}
