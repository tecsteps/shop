<?php

namespace App\Support;

class TokenAbilities
{
    /**
     * All grantable API token abilities and their descriptions
     * (spec 06 section 1.3).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'read-products' => __('List and view products'),
            'write-products' => __('Create, update, delete products'),
            'read-orders' => __('List and view orders'),
            'write-orders' => __('Update orders, create fulfillments'),
            'read-customers' => __('List and view customers'),
            'write-customers' => __('Update customers'),
            'read-collections' => __('List and view collections'),
            'write-collections' => __('Create, update, delete collections'),
            'read-discounts' => __('List and view discounts'),
            'write-discounts' => __('Create, update, delete discounts'),
            'read-analytics' => __('View analytics data'),
            'read-settings' => __('View store settings'),
            'write-settings' => __('Update store settings'),
            'read-themes' => __('View themes and theme files'),
            'write-themes' => __('Create, update, delete, publish themes'),
            'read-content' => __('View pages and navigation menus'),
            'write-content' => __('Create, update, delete pages and navigation items'),
            'manage-platform' => __('Platform-level management (super-admin only)'),
        ];
    }

    /**
     * The ability names only.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }
}
