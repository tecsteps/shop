<?php

namespace Tests\Support;

use App\Models\User;

class CatalogTestHelper
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function productPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Classic T-Shirt',
            'description_html' => '<p>A comfortable cotton t-shirt.</p>',
            'vendor' => 'Acme Apparel',
            'product_type' => 'Apparel',
            'status' => 'draft',
            'tags' => ['organic', 'cotton'],
            'options' => [
                ['name' => 'Size', 'position' => 1],
            ],
            'variants' => [
                [
                    'sku' => 'TSH-S',
                    'barcode' => '1234567890123',
                    'price_amount' => 2500,
                    'compare_at_amount' => 3500,
                    'weight_g' => 200,
                    'requires_shipping' => true,
                    'is_default' => true,
                    'position' => 1,
                    'status' => 'active',
                    'option_values' => [
                        ['option_name' => 'Size', 'value' => 'Small'],
                    ],
                    'inventory' => ['quantity_on_hand' => 50, 'policy' => 'deny'],
                ],
            ],
        ], $overrides);
    }

    /**
     * @param  array<int, string>  $abilities
     * @return array<string, string>
     */
    public static function adminHeaders(User $user, array $abilities = ['read-products', 'write-products', 'read-collections', 'write-collections', 'read-settings', 'write-settings']): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test', $abilities)->plainTextToken];
    }
}
