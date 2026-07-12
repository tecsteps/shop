<?php

namespace App\Policies\Concerns;

use App\Models\Store;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

trait ChecksStoreRoles
{
    /** @param list<string> $roles */
    protected function hasRole(User $user, Model|Store|null $resource, array $roles): bool
    {
        $store = $resource instanceof Store ? $resource : $this->storeFor($resource);
        $store ??= app()->bound('current_store') ? app('current_store') : null;
        if ($store === null) {
            return false;
        }
        $role = $user->roleForStore($store);
        $value = $role instanceof BackedEnum ? (string) $role->value : ($role === null ? null : (string) $role);

        return $value !== null && in_array($value, $roles, true);
    }

    private function storeFor(?Model $resource): ?Store
    {
        if ($resource === null) {
            return null;
        }
        if (isset($resource->store_id)) {
            return Store::query()->find($resource->store_id);
        }
        if (method_exists($resource, 'store')) {
            return $resource->store;
        }
        if (method_exists($resource, 'order')) {
            return $resource->order?->store;
        }

        return null;
    }
}
