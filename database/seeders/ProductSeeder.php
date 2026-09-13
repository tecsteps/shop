<?php

namespace Database\Seeders;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\CollectionProduct;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\VariantOptionValue;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ($this->fashionProducts() as $definition) {
            $this->seedProduct($fashion, $definition);
        }

        foreach ($this->electronicsProducts() as $definition) {
            $this->seedProduct($electronics, $definition);
        }

        $this->seedAssignments($fashion, [
            'new-arrivals' => ['classic-cotton-t-shirt', 'premium-slim-fit-jeans', 'organic-hoodie', 'running-sneakers', 'chino-shorts', 'bucket-hat', 'cashmere-overcoat'],
            't-shirts' => ['classic-cotton-t-shirt', 'graphic-print-tee', 'v-neck-linen-tee', 'striped-polo-shirt'],
            'pants-jeans' => ['premium-slim-fit-jeans', 'cargo-pants', 'chino-shorts', 'wide-leg-trousers'],
            'sale' => ['premium-slim-fit-jeans', 'striped-polo-shirt', 'wide-leg-trousers'],
        ]);

        $this->seedAssignments($electronics, [
            'featured' => ['pro-laptop-15', 'wireless-headphones', 'mechanical-keyboard'],
            'accessories' => ['usb-c-cable-2m', 'monitor-stand'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function seedProduct(Store $store, array $definition): void
    {
        $product = Product::query()->updateOrCreate(
            ['store_id' => $store->getKey(), 'handle' => $definition['handle']],
            [
                'title' => $definition['title'],
                'status' => $definition['status'],
                'description_html' => $definition['description_html'],
                'vendor' => $definition['vendor'],
                'product_type' => $definition['product_type'],
                'tags' => $definition['tags'],
                'published_at' => $definition['published_at'],
            ]
        );

        $optionIds = [];

        foreach (array_values($definition['options']) as $position => $option) {
            $productOption = ProductOption::query()->updateOrCreate(
                ['product_id' => $product->getKey(), 'name' => $option['name']],
                ['position' => $position]
            );

            $valueIds = [];

            foreach ($option['values'] as $valuePosition => $value) {
                $optionValue = ProductOptionValue::query()->updateOrCreate(
                    ['product_option_id' => $productOption->getKey(), 'value' => $value],
                    ['position' => $valuePosition]
                );

                $valueIds[$value] = $optionValue->getKey();
            }

            $optionIds[$option['name']] = $valueIds;
        }

        $combinations = $this->combinations($definition['options']);

        foreach ($combinations as $position => $combination) {
            $sku = $this->skuFor($definition, $combination, $position);

            $variant = ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->getKey(), 'sku' => $sku],
                [
                    'barcode' => fake()->unique()->ean13(),
                    'price_amount' => $definition['prices'][$position] ?? $definition['price'],
                    'compare_at_amount' => $definition['compare_at'],
                    'currency' => 'EUR',
                    'weight_g' => $definition['weight_g'],
                    'requires_shipping' => $definition['requires_shipping'],
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => VariantStatus::Active,
                ]
            );

            foreach ($combination as $optionName => $value) {
                VariantOptionValue::query()->firstOrCreate([
                    'variant_id' => $variant->getKey(),
                    'product_option_value_id' => $optionIds[$optionName][$value],
                ]);
            }

            InventoryItem::query()->updateOrCreate(
                ['variant_id' => $variant->getKey()],
                [
                    'store_id' => $store->getKey(),
                    'quantity_on_hand' => $definition['inventory'],
                    'quantity_reserved' => 0,
                    'policy' => $definition['policy'],
                ]
            );
        }
    }

    /**
     * @param  array<int, array{name: string, values: array<int, string>}>  $options
     * @return array<int, array<string, string>>
     */
    protected function combinations(array $options): array
    {
        if ($options === []) {
            return [[]];
        }

        $combinations = [[]];

        foreach ($options as $option) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($option['values'] as $value) {
                    $next[] = [...$combination, $option['name'] => $value];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, string>  $combination
     */
    protected function skuFor(array $definition, array $combination, int $position): string
    {
        if (isset($definition['skus'][$position])) {
            return $definition['skus'][$position];
        }

        $suffix = array_map(
            fn (string $value): string => $this->abbreviate($value),
            array_values($combination)
        );

        return $definition['sku_prefix'].($suffix === [] ? '' : '-'.implode('-', $suffix));
    }

    protected function abbreviate(string $value): string
    {
        $map = [
            'White' => 'WHT', 'Black' => 'BLK', 'Navy' => 'NVY', 'Blue' => 'BLU',
            'Brown' => 'BRN', 'Beige' => 'BEG', 'Olive' => 'OLV', 'Sky Blue' => 'SKY',
            'Khaki' => 'KHK', 'Sand' => 'SND', 'Grey' => 'GRY', 'Burgundy' => 'BRG',
            'Natural' => 'NAT', 'Camel' => 'CML', 'Charcoal' => 'CHR', 'Silver' => 'SLV',
            'Gray' => 'GRY',
        ];

        if (isset($map[$value])) {
            return $map[$value];
        }

        $clean = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));

        if (strlen($clean) <= 3 || preg_match('/[0-9]/', $clean) === 1) {
            return $clean;
        }

        return substr($clean, 0, 3);
    }

    /**
     * @param  array<string, array<int, string>>  $assignments
     */
    protected function seedAssignments(Store $store, array $assignments): void
    {
        foreach ($assignments as $handle => $productHandles) {
            $collection = Collection::query()
                ->where('store_id', $store->getKey())
                ->where('handle', $handle)
                ->firstOrFail();

            CollectionProduct::query()
                ->where('collection_id', $collection->getKey())
                ->delete();

            foreach ($productHandles as $position => $productHandle) {
                $product = Product::query()
                    ->where('store_id', $store->getKey())
                    ->where('handle', $productHandle)
                    ->firstOrFail();

                CollectionProduct::query()->create([
                    'collection_id' => $collection->getKey(),
                    'product_id' => $product->getKey(),
                    'position' => $position,
                ]);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fashionProducts(): array
    {
        $active = [
            'status' => ProductStatus::Active,
            'published_at' => now(),
            'compare_at' => null,
            'requires_shipping' => true,
            'policy' => InventoryPolicy::Deny,
            'prices' => [],
            'skus' => [],
        ];

        return [
            [
                ...$active,
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'description_html' => '<p>A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.</p>',
                'sku_prefix' => 'ACME-CTSH',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
                'price' => 2499, 'weight_g' => 200, 'inventory' => 15,
            ],
            [
                ...$active,
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description_html' => '<p>Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.</p>',
                'sku_prefix' => 'ACME-JEAN',
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
                'price' => 7999, 'compare_at' => 9999, 'weight_g' => 800, 'inventory' => 8,
            ],
            [
                ...$active,
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Made from 100% organic cotton. Warm, soft, and sustainably produced.</p>',
                'sku_prefix' => 'ACME-HOOD',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 5999, 'weight_g' => 500, 'inventory' => 20,
            ],
            [
                ...$active,
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description_html' => '<p>Genuine leather belt with brushed metal buckle. A wardrobe essential.</p>',
                'sku_prefix' => 'ACME-BELT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
                'price' => 3499, 'weight_g' => 150, 'inventory' => 25,
            ],
            [
                ...$active,
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description_html' => '<p>Lightweight running sneakers with responsive cushioning and breathable mesh upper.</p>',
                'sku_prefix' => 'ACME-RUN',
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
                'price' => 11999, 'weight_g' => 600, 'inventory' => 5,
            ],
            [
                ...$active,
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description_html' => '<p>Bold graphic print on soft cotton. Express yourself with this statement piece.</p>',
                'sku_prefix' => 'ACME-GRAPH',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 2999, 'weight_g' => 210, 'inventory' => 18,
            ],
            [
                ...$active,
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description_html' => '<p>Lightweight linen blend v-neck. Perfect for warm summer days.</p>',
                'sku_prefix' => 'ACME-VNECK',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
                'price' => 3499, 'weight_g' => 180, 'inventory' => 12,
            ],
            [
                ...$active,
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description_html' => '<p>Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.</p>',
                'sku_prefix' => 'ACME-POLO',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 2799, 'compare_at' => 3999, 'weight_g' => 250, 'inventory' => 10,
            ],
            [
                ...$active,
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description_html' => '<p>Utility cargo pants with multiple pockets. Durable cotton twill construction.</p>',
                'sku_prefix' => 'ACME-CARGO',
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
                'price' => 5499, 'weight_g' => 700, 'inventory' => 14,
            ],
            [
                ...$active,
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.</p>',
                'sku_prefix' => 'ACME-CHINO',
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
                'price' => 3999, 'weight_g' => 350, 'inventory' => 16,
            ],
            [
                ...$active,
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description_html' => '<p>Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.</p>',
                'sku_prefix' => 'ACME-TROUS',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ],
                'price' => 4999, 'compare_at' => 6999, 'weight_g' => 550, 'inventory' => 7,
            ],
            [
                ...$active,
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description_html' => '<p>Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.</p>',
                'sku_prefix' => 'ACME-SCARF',
                'options' => [
                    ['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']],
                ],
                'price' => 2999, 'weight_g' => 120, 'inventory' => 30,
            ],
            [
                ...$active,
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description_html' => '<p>Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.</p>',
                'sku_prefix' => 'ACME-TOTE',
                'options' => [
                    ['name' => 'Color', 'values' => ['Natural', 'Black']],
                ],
                'price' => 1999, 'weight_g' => 300, 'inventory' => 40,
            ],
            [
                ...$active,
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Lightweight bucket hat for sun protection. Packable design, washed cotton twill.</p>',
                'sku_prefix' => 'ACME-HAT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
                'price' => 2499, 'weight_g' => 80, 'inventory' => 22,
            ],
            [
                ...$active,
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'status' => ProductStatus::Draft,
                'published_at' => null,
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'description_html' => '<p>Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.</p>',
                'sku_prefix' => 'ACME-JACKET',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 14999, 'weight_g' => 900, 'inventory' => 0,
            ],
            [
                ...$active,
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => ProductStatus::Archived,
                'published_at' => now()->subMonths(6),
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description_html' => '<p>Lightweight waterproof raincoat. This product has been discontinued.</p>',
                'sku_prefix' => 'ACME-RAIN',
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
                'price' => 8999, 'weight_g' => 400, 'inventory' => 3,
            ],
            [
                ...$active,
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description_html' => '<p>Limited edition collaboration sneakers. Once they are gone, they are gone.</p>',
                'sku_prefix' => 'ACME-LESNK',
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']],
                ],
                'price' => 15999, 'weight_g' => 650, 'inventory' => 0,
            ],
            [
                ...$active,
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description_html' => '<p>Classic denim jacket. Currently on backorder - ships within 2-3 weeks.</p>',
                'sku_prefix' => 'ACME-DJACK',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 9999, 'weight_g' => 750, 'inventory' => 0,
                'policy' => InventoryPolicy::Continue,
            ],
            [
                ...$active,
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description_html' => '<p>Digital gift card delivered via email. The perfect gift when you are not sure what to choose.</p>',
                'sku_prefix' => 'ACME-GIFT',
                'options' => [
                    ['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']],
                ],
                'price' => 2500,
                'prices' => [2500, 5000, 10000],
                'skus' => ['ACME-GIFT-25', 'ACME-GIFT-50', 'ACME-GIFT-100'],
                'weight_g' => 0,
                'requires_shipping' => false,
                'inventory' => 9999,
            ],
            [
                ...$active,
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description_html' => '<p>Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.</p>',
                'sku_prefix' => 'ACME-COAT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Camel', 'Charcoal']],
                ],
                'price' => 49999, 'weight_g' => 1200, 'inventory' => 3,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function electronicsProducts(): array
    {
        $active = [
            'status' => ProductStatus::Active,
            'published_at' => now(),
            'compare_at' => null,
            'requires_shipping' => true,
            'policy' => InventoryPolicy::Deny,
            'prices' => [],
            'skus' => [],
        ];

        return [
            [
                ...$active,
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['new', 'popular'],
                'description_html' => '<p>High-performance 15-inch laptop for professionals.</p>',
                'sku_prefix' => 'TECH-LAP15',
                'options' => [
                    ['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']],
                ],
                'price' => 99999,
                'prices' => [99999, 119999, 149999],
                'weight_g' => 1800,
                'inventory' => 10,
            ],
            [
                ...$active,
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['popular'],
                'description_html' => '<p>Noise-cancelling wireless headphones with 30-hour battery life.</p>',
                'sku_prefix' => 'AUDIO-HP',
                'options' => [
                    ['name' => 'Color', 'values' => ['Black', 'Silver']],
                ],
                'price' => 14999, 'weight_g' => 250, 'inventory' => 25,
            ],
            [
                ...$active,
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => ['popular'],
                'description_html' => '<p>Durable 2-meter USB-C charging and data cable.</p>',
                'sku_prefix' => 'CABLE-USBC-2M',
                'options' => [],
                'price' => 1299, 'weight_g' => 50, 'inventory' => 200,
            ],
            [
                ...$active,
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['new'],
                'description_html' => '<p>Hot-swappable mechanical keyboard with RGB backlight.</p>',
                'sku_prefix' => 'KEY-MECH',
                'options' => [
                    ['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']],
                ],
                'price' => 12999, 'weight_g' => 1100, 'inventory' => 15,
            ],
            [
                ...$active,
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description_html' => '<p>Ergonomic aluminium monitor stand with cable management.</p>',
                'sku_prefix' => 'DESK-STAND',
                'options' => [],
                'price' => 4999, 'weight_g' => 2500, 'inventory' => 30,
            ],
        ];
    }
}
