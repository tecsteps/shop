<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');
        $electronics = Store::query()->firstWhere('handle', 'acme-electronics');

        if ($fashion) {
            $this->seedFashionProducts($fashion);
        }

        if ($electronics) {
            $this->seedElectronicsProducts($electronics);
        }
    }

    private function seedFashionProducts(Store $store): void
    {
        $this->upsertProduct(
            $store,
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'status' => 'active',
                'description_html' => '<p>A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.</p>',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'published_at' => now(),
            ],
            [
                'Size' => ['S', 'M', 'L', 'XL'],
                'Color' => ['White', 'Black'],
            ],
            [
                ['sku' => 'ACME-CTSH-S-WHT', 'options' => ['Size' => 'S', 'Color' => 'White'], 'is_default' => true],
                ['sku' => 'ACME-CTSH-M-BLK', 'options' => ['Size' => 'M', 'Color' => 'Black']],
                ['sku' => 'ACME-CTSH-L-WHT', 'options' => ['Size' => 'L', 'Color' => 'White']],
                ['sku' => 'ACME-CTSH-XL-BLK', 'options' => ['Size' => 'XL', 'Color' => 'Black']],
            ],
            ['new-arrivals', 't-shirts'],
            [
                'price_amount' => 2499,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 200,
                'requires_shipping' => true,
                'inventory' => 15,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'status' => 'active',
                'description_html' => '<p>Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.</p>',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'published_at' => now(),
            ],
            [
                'Size' => ['30', '32', '34'],
                'Color' => ['Blue', 'Black'],
            ],
            [
                ['sku' => 'ACME-JEAN-30-BLU', 'options' => ['Size' => '30', 'Color' => 'Blue'], 'is_default' => true],
                ['sku' => 'ACME-JEAN-32-BLK', 'options' => ['Size' => '32', 'Color' => 'Black']],
                ['sku' => 'ACME-JEAN-34-BLU', 'options' => ['Size' => '34', 'Color' => 'Blue']],
            ],
            ['new-arrivals', 'pants-jeans', 'sale'],
            [
                'price_amount' => 7999,
                'compare_at_amount' => 9999,
                'currency' => 'EUR',
                'weight_g' => 800,
                'requires_shipping' => true,
                'inventory' => 8,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'status' => 'draft',
                'description_html' => '<p>Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.</p>',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'published_at' => null,
            ],
            ['Size' => ['S', 'M', 'L']],
            [
                ['sku' => 'ACME-WJKT-S', 'options' => ['Size' => 'S'], 'is_default' => true],
                ['sku' => 'ACME-WJKT-M', 'options' => ['Size' => 'M']],
                ['sku' => 'ACME-WJKT-L', 'options' => ['Size' => 'L']],
            ],
            [],
            [
                'price_amount' => 14999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 900,
                'requires_shipping' => true,
                'inventory' => 0,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => 'archived',
                'description_html' => '<p>Lightweight waterproof raincoat. This product has been discontinued.</p>',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'published_at' => now()->subMonths(6),
            ],
            ['Size' => ['M', 'L']],
            [
                ['sku' => 'ACME-RAIN-M', 'options' => ['Size' => 'M'], 'is_default' => true],
                ['sku' => 'ACME-RAIN-L', 'options' => ['Size' => 'L']],
            ],
            [],
            [
                'price_amount' => 8999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 400,
                'requires_shipping' => true,
                'inventory' => 3,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'status' => 'active',
                'description_html' => '<p>Limited edition collaboration sneakers. Once they are gone, they are gone.</p>',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'published_at' => now(),
            ],
            ['Size' => ['EU 40', 'EU 42', 'EU 44']],
            [
                ['sku' => 'ACME-LTD-40', 'options' => ['Size' => 'EU 40'], 'is_default' => true],
                ['sku' => 'ACME-LTD-42', 'options' => ['Size' => 'EU 42']],
                ['sku' => 'ACME-LTD-44', 'options' => ['Size' => 'EU 44']],
            ],
            [],
            [
                'price_amount' => 15999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 650,
                'requires_shipping' => true,
                'inventory' => 0,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'status' => 'active',
                'description_html' => '<p>Classic denim jacket. Currently on backorder - ships within 2-3 weeks.</p>',
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'published_at' => now(),
            ],
            ['Size' => ['S', 'M', 'L', 'XL']],
            [
                ['sku' => 'ACME-BACK-S', 'options' => ['Size' => 'S'], 'is_default' => true],
                ['sku' => 'ACME-BACK-M', 'options' => ['Size' => 'M']],
                ['sku' => 'ACME-BACK-L', 'options' => ['Size' => 'L']],
                ['sku' => 'ACME-BACK-XL', 'options' => ['Size' => 'XL']],
            ],
            [],
            [
                'price_amount' => 9999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 750,
                'requires_shipping' => true,
                'inventory' => 0,
                'inventory_policy' => 'continue',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'status' => 'active',
                'description_html' => '<p>Digital gift card delivered via email. The perfect gift when you are not sure what to choose.</p>',
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'published_at' => now(),
            ],
            ['Amount' => ['25 EUR', '50 EUR', '100 EUR']],
            [
                ['sku' => 'ACME-GIFT-25', 'options' => ['Amount' => '25 EUR'], 'is_default' => true, 'price_amount' => 2500],
                ['sku' => 'ACME-GIFT-50', 'options' => ['Amount' => '50 EUR'], 'price_amount' => 5000],
                ['sku' => 'ACME-GIFT-100', 'options' => ['Amount' => '100 EUR'], 'price_amount' => 10000],
            ],
            [],
            [
                'price_amount' => 2500,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 0,
                'requires_shipping' => false,
                'inventory' => 9999,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'status' => 'active',
                'description_html' => '<p>Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.</p>',
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'published_at' => now(),
            ],
            [
                'Size' => ['S', 'M', 'L'],
                'Color' => ['Camel', 'Charcoal'],
            ],
            [
                ['sku' => 'ACME-CASH-S-CAM', 'options' => ['Size' => 'S', 'Color' => 'Camel'], 'is_default' => true],
                ['sku' => 'ACME-CASH-M-CHR', 'options' => ['Size' => 'M', 'Color' => 'Charcoal']],
                ['sku' => 'ACME-CASH-L-CAM', 'options' => ['Size' => 'L', 'Color' => 'Camel']],
                ['sku' => 'ACME-CASH-M-CAM', 'options' => ['Size' => 'M', 'Color' => 'Camel']],
            ],
            ['new-arrivals'],
            [
                'price_amount' => 49999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 1200,
                'requires_shipping' => true,
                'inventory' => 3,
                'inventory_policy' => 'deny',
            ]
        );
    }

    private function seedElectronicsProducts(Store $store): void
    {
        $this->upsertProduct(
            $store,
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'status' => 'active',
                'description_html' => '<p>High-performance laptop for professional workloads.</p>',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['featured', 'new'],
                'published_at' => now(),
            ],
            ['Storage' => ['256GB', '512GB', '1TB']],
            [
                ['sku' => 'TECH-LAP-256', 'options' => ['Storage' => '256GB'], 'is_default' => true, 'price_amount' => 99999],
                ['sku' => 'TECH-LAP-512', 'options' => ['Storage' => '512GB'], 'price_amount' => 119999],
                ['sku' => 'TECH-LAP-1TB', 'options' => ['Storage' => '1TB'], 'price_amount' => 149999],
            ],
            ['featured'],
            [
                'price_amount' => 99999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 1800,
                'requires_shipping' => true,
                'inventory' => 10,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'status' => 'active',
                'description_html' => '<p>Noise-cancelling headphones with all-day battery life.</p>',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['featured'],
                'published_at' => now(),
            ],
            ['Color' => ['Black', 'Silver']],
            [
                ['sku' => 'TECH-HDPH-BLK', 'options' => ['Color' => 'Black'], 'is_default' => true],
                ['sku' => 'TECH-HDPH-SLV', 'options' => ['Color' => 'Silver']],
            ],
            ['featured'],
            [
                'price_amount' => 14999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 250,
                'requires_shipping' => true,
                'inventory' => 25,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'status' => 'active',
                'description_html' => '<p>Durable USB-C cable for charging and data transfer.</p>',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => ['accessories'],
                'published_at' => now(),
            ],
            [],
            [
                ['sku' => 'TECH-CABLE-2M', 'is_default' => true],
            ],
            ['accessories'],
            [
                'price_amount' => 1299,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 50,
                'requires_shipping' => true,
                'inventory' => 200,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'status' => 'active',
                'description_html' => '<p>Mechanical keyboard with premium switches.</p>',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['featured'],
                'published_at' => now(),
            ],
            ['Switch Type' => ['Red', 'Blue', 'Brown']],
            [
                ['sku' => 'TECH-KEY-RED', 'options' => ['Switch Type' => 'Red'], 'is_default' => true],
                ['sku' => 'TECH-KEY-BLU', 'options' => ['Switch Type' => 'Blue']],
                ['sku' => 'TECH-KEY-BRN', 'options' => ['Switch Type' => 'Brown']],
            ],
            ['featured'],
            [
                'price_amount' => 12999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 1100,
                'requires_shipping' => true,
                'inventory' => 15,
                'inventory_policy' => 'deny',
            ]
        );

        $this->upsertProduct(
            $store,
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'status' => 'active',
                'description_html' => '<p>Ergonomic monitor stand with storage compartment.</p>',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => ['accessories'],
                'published_at' => now(),
            ],
            [],
            [
                ['sku' => 'TECH-STAND-STD', 'is_default' => true],
            ],
            ['accessories'],
            [
                'price_amount' => 4999,
                'compare_at_amount' => null,
                'currency' => 'EUR',
                'weight_g' => 2500,
                'requires_shipping' => true,
                'inventory' => 30,
                'inventory_policy' => 'deny',
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $productData
     * @param  array<string, list<string>>  $options
     * @param  list<array<string, mixed>>  $variants
     * @param  list<string>  $collectionHandles
     * @param  array<string, mixed>  $defaults
     */
    private function upsertProduct(
        Store $store,
        array $productData,
        array $options,
        array $variants,
        array $collectionHandles,
        array $defaults
    ): void {
        $product = Product::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => $productData['handle'],
            ],
            [
                'title' => $productData['title'],
                'status' => $productData['status'],
                'description_html' => $productData['description_html'],
                'vendor' => $productData['vendor'],
                'product_type' => $productData['product_type'],
                'tags' => $productData['tags'],
                'published_at' => $productData['published_at'],
            ]
        );

        $collectionIds = Collection::query()
            ->where('store_id', $store->id)
            ->whereIn('handle', $collectionHandles)
            ->pluck('id')
            ->values();

        $syncPayload = [];

        foreach ($collectionIds as $position => $collectionId) {
            $syncPayload[$collectionId] = ['position' => $position];
        }

        $product->collections()->sync($syncPayload);

        $optionMap = [];

        foreach (array_values($options) as $position => $unused) {
            // Preserve key order by iterating original array in a second pass.
        }

        $optionPosition = 0;

        foreach ($options as $optionName => $values) {
            $option = ProductOption::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => $optionName,
                ],
                [
                    'position' => $optionPosition,
                ]
            );

            foreach (array_values($values) as $valuePosition => $value) {
                $valueModel = ProductOptionValue::query()->updateOrCreate(
                    [
                        'product_option_id' => $option->id,
                        'value' => $value,
                    ],
                    [
                        'position' => $valuePosition,
                    ]
                );

                $optionMap[$optionName][$value] = $valueModel->id;
            }

            $optionPosition++;
        }

        foreach (array_values($variants) as $position => $variantData) {
            $variant = ProductVariant::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                ],
                [
                    'barcode' => $variantData['barcode'] ?? null,
                    'price_amount' => $variantData['price_amount'] ?? $defaults['price_amount'],
                    'compare_at_amount' => $variantData['compare_at_amount'] ?? $defaults['compare_at_amount'],
                    'currency' => $variantData['currency'] ?? $defaults['currency'],
                    'weight_g' => $variantData['weight_g'] ?? $defaults['weight_g'],
                    'requires_shipping' => $variantData['requires_shipping'] ?? $defaults['requires_shipping'],
                    'is_default' => $variantData['is_default'] ?? false,
                    'position' => $position,
                    'status' => $variantData['status'] ?? 'active',
                ]
            );

            $optionValueIds = [];

            foreach (($variantData['options'] ?? []) as $optionName => $value) {
                $optionValueId = $optionMap[$optionName][$value] ?? null;

                if ($optionValueId) {
                    $optionValueIds[] = $optionValueId;
                }
            }

            $variant->optionValues()->sync($optionValueIds);

            InventoryItem::query()->updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => $variantData['inventory'] ?? $defaults['inventory'],
                    'quantity_reserved' => 0,
                    'policy' => $variantData['inventory_policy'] ?? $defaults['inventory_policy'],
                ]
            );
        }
    }
}
