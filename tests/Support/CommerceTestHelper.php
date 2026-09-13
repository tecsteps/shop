<?php

namespace Tests\Support;

use App\Enums\ProductStatus;
use App\Enums\ShippingRateType;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Services\CartService;
use App\Services\ProductService;

class CommerceTestHelper
{
    protected static int $skuSequence = 0;

    /**
     * Create an active product with one active variant and inventory.
     *
     * @param  array<string, mixed>  $variantOverrides
     * @param  array<string, mixed>  $inventoryOverrides
     */
    public static function purchasableVariant(
        Store $store,
        array $variantOverrides = [],
        array $inventoryOverrides = [],
        array $productOverrides = [],
    ): ProductVariant {
        self::$skuSequence++;

        $payload = array_merge([
            'title' => 'Test Product '.self::$skuSequence,
            'status' => 'draft',
            'variants' => [
                array_merge([
                    'sku' => 'TEST-SKU-'.self::$skuSequence.'-'.$store->getKey(),
                    'price_amount' => 2500,
                    'weight_g' => 200,
                    'requires_shipping' => true,
                    'is_default' => true,
                    'position' => 1,
                    'status' => 'active',
                    'inventory' => array_merge(
                        ['quantity_on_hand' => 50, 'policy' => 'deny'],
                        $inventoryOverrides
                    ),
                ], $variantOverrides),
            ],
        ], $productOverrides);

        $product = app(ProductService::class)->create($store, $payload);
        app(ProductService::class)->transitionStatus($product->refresh(), ProductStatus::Active);

        return $product->refresh()->productVariants()->first();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function address(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'company' => null,
            'address1' => '123 Main St',
            'address2' => null,
            'city' => 'Berlin',
            'province' => 'Berlin',
            'province_code' => 'BE',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
            'phone' => null,
        ], $overrides);
    }

    public static function storefrontUrl(StoreDomain $domain, string $path): string
    {
        return "http://{$domain->hostname}{$path}";
    }

    /**
     * Create a cart with one line per spec entry.
     *
     * @param  array<int, array{price?: int, qty?: int, weight?: int, shipping?: bool, stock?: int}>  $lines
     */
    public static function cartWithLines(Store $store, array $lines = [['price' => 2500, 'qty' => 2]]): Cart
    {
        $cart = app(CartService::class)->create($store);

        foreach ($lines as $spec) {
            $variant = self::purchasableVariant(
                $store,
                [
                    'price_amount' => $spec['price'] ?? 2500,
                    'weight_g' => $spec['weight'] ?? 200,
                    'requires_shipping' => $spec['shipping'] ?? true,
                ],
                ['quantity_on_hand' => $spec['stock'] ?? 50]
            );

            app(CartService::class)->addLine($cart->refresh(), $variant->id, $spec['qty'] ?? 1);
        }

        return $cart->refresh();
    }

    /**
     * @param  array<int, string>  $countries
     */
    public static function flatShippingRate(Store $store, array $countries = ['DE'], int $amount = 499): ShippingRate
    {
        $zone = ShippingZone::factory()->create([
            'store_id' => $store->id,
            'countries_json' => $countries,
            'regions_json' => [],
        ]);

        return ShippingRate::factory()->create([
            'zone_id' => $zone->id,
            'type' => ShippingRateType::Flat,
            'config_json' => ['amount' => $amount],
            'is_active' => true,
        ]);
    }
}
