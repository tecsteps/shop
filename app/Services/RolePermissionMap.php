<?php

namespace App\Services;

use App\Enums\StoreUserRole;

/**
 * Maps store roles to API token abilities, derived from the permission
 * matrix in spec 06 section 2.2. Used by the store membership endpoint.
 */
class RolePermissionMap
{
    /**
     * @return array<int, string>
     */
    public static function for(StoreUserRole $role): array
    {
        return match ($role) {
            StoreUserRole::Owner, StoreUserRole::Admin => [
                'read-products',
                'write-products',
                'read-orders',
                'write-orders',
                'read-customers',
                'write-customers',
                'read-collections',
                'write-collections',
                'read-discounts',
                'write-discounts',
                'read-analytics',
                'read-settings',
                'write-settings',
                'read-themes',
                'write-themes',
                'read-content',
                'write-content',
            ],
            StoreUserRole::Staff => [
                'read-products',
                'write-products',
                'read-orders',
                'write-orders',
                'read-customers',
                'write-customers',
                'read-collections',
                'write-collections',
                'read-discounts',
                'write-discounts',
                'read-analytics',
                'read-content',
                'write-content',
            ],
            StoreUserRole::Support => [
                'read-products',
                'read-orders',
                'read-customers',
                'read-collections',
                'read-discounts',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function allAbilities(): array
    {
        return [
            ...self::for(StoreUserRole::Owner),
            'manage-platform',
        ];
    }
}
