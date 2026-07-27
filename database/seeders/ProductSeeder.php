<?php

namespace Database\Seeders;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use App\Services\SearchService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function __construct(
        private ProductService $products,
        private SearchService $search,
    ) {}

    /**
     * Create the full demo catalog: products with options, variants,
     * inventory, and collection assignments (spec 07 §3.10, §4).
     *
     * No ProductMedia records are seeded on purpose (spec 07 §3.10 note):
     * the storefront renders a placeholder for products without images.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $fashionProducts = [];

            foreach ($this->fashionDefinitions() as $definition) {
                $fashionProducts[$definition['handle']] = $this->seedProduct($fashion, $definition);
            }

            foreach ($this->electronicsDefinitions() as $definition) {
                $this->seedProduct($electronics, $definition);
            }

            $this->assignCollections($fashion, $fashionProducts);
            $this->assignElectronicsCollections($electronics);

            $this->search->reindex($fashion);
            $this->search->reindex($electronics);
        });
    }

    /**
     * Create or update one product with its full entity graph.
     *
     * @param  array<string, mixed>  $definition
     */
    private function seedProduct(Store $store, array $definition): Product
    {
        $data = [
            'title' => $definition['title'],
            'handle' => $definition['handle'],
            'status' => $definition['status'],
            'description_html' => '<p>'.$definition['description'].'</p>',
            'vendor' => $definition['vendor'],
            'product_type' => $definition['product_type'],
            'tags' => $definition['tags'],
            'published_at' => $definition['published_at'](),
            'options' => $definition['options'],
            'variant_defaults' => $definition['defaults'],
            'variants' => $this->buildVariants($definition),
        ];

        $existing = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', $definition['handle'])
            ->first();

        return $existing !== null
            ? $this->products->update($existing, $data)
            : $this->products->create($store, $data);
    }

    /**
     * Expand the cartesian product of the option values into per-variant
     * overrides (SKU + inventory), merged with any explicit variant data.
     *
     * @param  array<string, mixed>  $definition
     * @return list<array<string, mixed>>
     */
    private function buildVariants(array $definition): array
    {
        if (isset($definition['variants'])) {
            return $definition['variants'];
        }

        if ($definition['options'] === []) {
            return [$definition['single_variant']];
        }

        $valueSets = array_map(
            fn (array $option): array => $option['values'],
            $definition['options'],
        );

        $variants = [];

        foreach ($this->cartesian($valueSets) as $combo) {
            $variants[] = [
                'option_values' => $combo,
                'sku' => $this->sku($definition['code'], $combo),
                'inventory' => $definition['inventory'],
            ];
        }

        return $variants;
    }

    /**
     * Cartesian product of the given sets (last set varies fastest, matching
     * the VariantMatrixService ordering).
     *
     * @param  list<list<string>>  $sets
     * @return list<list<string>>
     */
    private function cartesian(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $set) {
            $next = [];

            foreach ($result as $combination) {
                foreach ($set as $value) {
                    $next[] = array_merge($combination, [$value]);
                }
            }

            $result = $next;
        }

        return $result;
    }

    /**
     * Build a deterministic SKU from the product code and option values.
     *
     * @param  list<string>  $optionValues
     */
    private function sku(string $code, array $optionValues): string
    {
        $parts = array_map(
            fn (string $value): string => trim(strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '-', $value)), '-'),
            $optionValues,
        );

        return 'ACME-'.$code.'-'.implode('-', $parts);
    }

    /**
     * Link products to the Acme Fashion collections (spec 07 §4).
     *
     * @param  array<string, Product>  $productsByHandle
     */
    private function assignCollections(Store $store, array $productsByHandle): void
    {
        $assignments = [
            'new-arrivals' => ['classic-cotton-t-shirt', 'premium-slim-fit-jeans', 'organic-hoodie', 'running-sneakers', 'chino-shorts', 'bucket-hat', 'cashmere-overcoat'],
            't-shirts' => ['classic-cotton-t-shirt', 'graphic-print-tee', 'v-neck-linen-tee', 'striped-polo-shirt'],
            'pants-jeans' => ['premium-slim-fit-jeans', 'cargo-pants', 'chino-shorts', 'wide-leg-trousers'],
            'sale' => ['premium-slim-fit-jeans', 'striped-polo-shirt', 'wide-leg-trousers'],
        ];

        foreach ($assignments as $collectionHandle => $productHandles) {
            $collection = Collection::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('handle', $collectionHandle)
                ->firstOrFail();

            $attach = [];

            foreach ($productHandles as $position => $productHandle) {
                $attach[$productsByHandle[$productHandle]->id] = ['position' => $position];
            }

            $collection->products()->sync($attach);
        }
    }

    /**
     * Link products to the Acme Electronics collections (spec 07 §4).
     */
    private function assignElectronicsCollections(Store $store): void
    {
        $assignments = [
            'featured' => ['pro-laptop-15', 'wireless-headphones', 'mechanical-keyboard'],
            'accessories' => ['usb-c-cable-2m', 'monitor-stand'],
        ];

        foreach ($assignments as $collectionHandle => $productHandles) {
            $collection = Collection::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('handle', $collectionHandle)
                ->firstOrFail();

            $attach = [];

            foreach ($productHandles as $position => $productHandle) {
                $product = Product::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('handle', $productHandle)
                    ->firstOrFail();

                $attach[$product->id] = ['position' => $position];
            }

            $collection->products()->sync($attach);
        }
    }

    /**
     * The 20 Acme Fashion products (spec 07 §3.10).
     *
     * @return list<array<string, mixed>>
     */
    private function fashionDefinitions(): array
    {
        $deny = fn (int $quantity): array => ['quantity_on_hand' => $quantity, 'policy' => InventoryPolicy::Deny];

        return [
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'description' => 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.',
                'published_at' => fn () => now(),
                'code' => 'CTSH',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
                'defaults' => ['price_amount' => 2499, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 200, 'requires_shipping' => true],
                'inventory' => $deny(15),
                'variants' => $this->cartesianVariants('CTSH', [['S', 'M', 'L', 'XL'], ['White', 'Black', 'Navy']], $deny(15), [
                    'White' => 'WHT', 'Black' => 'BLK', 'Navy' => 'NAV',
                ]),
            ],
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description' => 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.',
                'published_at' => fn () => now(),
                'code' => 'PSFJ',
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
                'defaults' => ['price_amount' => 7999, 'compare_at_amount' => 9999, 'currency' => 'EUR', 'weight_g' => 800, 'requires_shipping' => true],
                'inventory' => $deny(8),
            ],
            [
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description' => 'Made from 100% organic cotton. Warm, soft, and sustainably produced.',
                'published_at' => fn () => now(),
                'code' => 'OHOOD',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'defaults' => ['price_amount' => 5999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 500, 'requires_shipping' => true],
                'inventory' => $deny(20),
            ],
            [
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Genuine leather belt with brushed metal buckle. A wardrobe essential.',
                'published_at' => fn () => now(),
                'code' => 'LBELT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
                'defaults' => ['price_amount' => 3499, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 150, 'requires_shipping' => true],
                'inventory' => $deny(25),
            ],
            [
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description' => 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.',
                'published_at' => fn () => now(),
                'code' => 'RSNEAK',
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
                'defaults' => ['price_amount' => 11999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 600, 'requires_shipping' => true],
                'inventory' => $deny(5),
            ],
            [
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description' => 'Bold graphic print on soft cotton. Express yourself with this statement piece.',
                'published_at' => fn () => now(),
                'code' => 'GPRTEE',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'defaults' => ['price_amount' => 2999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 210, 'requires_shipping' => true],
                'inventory' => $deny(18),
            ],
            [
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description' => 'Lightweight linen blend v-neck. Perfect for warm summer days.',
                'published_at' => fn () => now(),
                'code' => 'VNLTEE',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
                'defaults' => ['price_amount' => 3499, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 180, 'requires_shipping' => true],
                'inventory' => $deny(12),
            ],
            [
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description' => 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.',
                'published_at' => fn () => now(),
                'code' => 'SPOLO',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'defaults' => ['price_amount' => 2799, 'compare_at_amount' => 3999, 'currency' => 'EUR', 'weight_g' => 250, 'requires_shipping' => true],
                'inventory' => $deny(10),
            ],
            [
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description' => 'Utility cargo pants with multiple pockets. Durable cotton twill construction.',
                'published_at' => fn () => now(),
                'code' => 'CARGO',
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
                'defaults' => ['price_amount' => 5499, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 700, 'requires_shipping' => true],
                'inventory' => $deny(14),
            ],
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description' => 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.',
                'published_at' => fn () => now(),
                'code' => 'CHSHORT',
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
                'defaults' => ['price_amount' => 3999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 350, 'requires_shipping' => true],
                'inventory' => $deny(16),
            ],
            [
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description' => 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.',
                'published_at' => fn () => now(),
                'code' => 'WLTROU',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ],
                'defaults' => ['price_amount' => 4999, 'compare_at_amount' => 6999, 'currency' => 'EUR', 'weight_g' => 550, 'requires_shipping' => true],
                'inventory' => $deny(7),
            ],
            [
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.',
                'published_at' => fn () => now(),
                'code' => 'WSCARF',
                'options' => [
                    ['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']],
                ],
                'defaults' => ['price_amount' => 2999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 120, 'requires_shipping' => true],
                'inventory' => $deny(30),
            ],
            [
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description' => 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.',
                'published_at' => fn () => now(),
                'code' => 'CTOTE',
                'options' => [
                    ['name' => 'Color', 'values' => ['Natural', 'Black']],
                ],
                'defaults' => ['price_amount' => 1999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 300, 'requires_shipping' => true],
                'inventory' => $deny(40),
            ],
            [
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description' => 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.',
                'published_at' => fn () => now(),
                'code' => 'BHAT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
                'defaults' => ['price_amount' => 2499, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 80, 'requires_shipping' => true],
                'inventory' => $deny(22),
            ],
            [
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'status' => ProductStatus::Draft,
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'description' => 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.',
                'published_at' => fn () => null,
                'code' => 'UWJACK',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'defaults' => ['price_amount' => 14999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 900, 'requires_shipping' => true],
                'inventory' => $deny(0),
            ],
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => ProductStatus::Archived,
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description' => 'Lightweight waterproof raincoat. This product has been discontinued.',
                'published_at' => fn () => now()->subMonths(6),
                'code' => 'DRAIN',
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
                'defaults' => ['price_amount' => 8999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 400, 'requires_shipping' => true],
                'inventory' => $deny(3),
            ],
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description' => 'Limited edition collaboration sneakers. Once they are gone, they are gone.',
                'published_at' => fn () => now(),
                'code' => 'LESNEAK',
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']],
                ],
                'defaults' => ['price_amount' => 15999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 650, 'requires_shipping' => true],
                'inventory' => $deny(0),
            ],
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description' => 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.',
                'published_at' => fn () => now(),
                'code' => 'BDJACK',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'defaults' => ['price_amount' => 9999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 750, 'requires_shipping' => true],
                'inventory' => ['quantity_on_hand' => 0, 'policy' => InventoryPolicy::Continue],
            ],
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description' => 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.',
                'published_at' => fn () => now(),
                'code' => 'GIFT',
                'options' => [
                    ['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']],
                ],
                'defaults' => ['price_amount' => 2500, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 0, 'requires_shipping' => false],
                'inventory' => $deny(9999),
                'variants' => [
                    ['option_values' => ['25 EUR'], 'sku' => 'ACME-GIFT-25', 'price_amount' => 2500, 'weight_g' => 0, 'requires_shipping' => false, 'inventory' => $deny(9999)],
                    ['option_values' => ['50 EUR'], 'sku' => 'ACME-GIFT-50', 'price_amount' => 5000, 'weight_g' => 0, 'requires_shipping' => false, 'inventory' => $deny(9999)],
                    ['option_values' => ['100 EUR'], 'sku' => 'ACME-GIFT-100', 'price_amount' => 10000, 'weight_g' => 0, 'requires_shipping' => false, 'inventory' => $deny(9999)],
                ],
            ],
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description' => 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.',
                'published_at' => fn () => now(),
                'code' => 'COVER',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Camel', 'Charcoal']],
                ],
                'defaults' => ['price_amount' => 49999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 1200, 'requires_shipping' => true],
                'inventory' => $deny(3),
            ],
        ];
    }

    /**
     * The 5 Acme Electronics products (spec 07 §3.10).
     *
     * @return list<array<string, mixed>>
     */
    private function electronicsDefinitions(): array
    {
        $deny = fn (int $quantity): array => ['quantity_on_hand' => $quantity, 'policy' => InventoryPolicy::Deny];

        return [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'status' => ProductStatus::Active,
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['new', 'popular'],
                'description' => 'Professional 15-inch laptop with a fast processor and all-day battery life.',
                'published_at' => fn () => now(),
                'code' => 'LAP15',
                'options' => [
                    ['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']],
                ],
                'defaults' => ['price_amount' => 99999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 1800, 'requires_shipping' => true],
                'inventory' => $deny(10),
                'variants' => [
                    ['option_values' => ['256GB'], 'sku' => 'ACME-LAP15-256GB', 'price_amount' => 99999, 'inventory' => $deny(10)],
                    ['option_values' => ['512GB'], 'sku' => 'ACME-LAP15-512GB', 'price_amount' => 119999, 'inventory' => $deny(10)],
                    ['option_values' => ['1TB'], 'sku' => 'ACME-LAP15-1TB', 'price_amount' => 149999, 'inventory' => $deny(10)],
                ],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'status' => ProductStatus::Active,
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['popular'],
                'description' => 'Wireless over-ear headphones with active noise cancellation.',
                'published_at' => fn () => now(),
                'code' => 'WHEAD',
                'options' => [
                    ['name' => 'Color', 'values' => ['Black', 'Silver']],
                ],
                'defaults' => ['price_amount' => 14999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 250, 'requires_shipping' => true],
                'inventory' => $deny(25),
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'status' => ProductStatus::Active,
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => [],
                'description' => 'Durable braided USB-C cable, 2 meters, fast charging and data transfer.',
                'published_at' => fn () => now(),
                'code' => 'USBC',
                'options' => [],
                'defaults' => [],
                'inventory' => $deny(200),
                'single_variant' => [
                    'sku' => 'ACME-USBC-2M',
                    'price_amount' => 1299,
                    'currency' => 'EUR',
                    'weight_g' => 50,
                    'requires_shipping' => true,
                    'inventory' => $deny(200),
                ],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'status' => ProductStatus::Active,
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['trending'],
                'description' => 'Compact mechanical keyboard with hot-swappable switches.',
                'published_at' => fn () => now(),
                'code' => 'MKEYB',
                'options' => [
                    ['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']],
                ],
                'defaults' => ['price_amount' => 12999, 'compare_at_amount' => null, 'currency' => 'EUR', 'weight_g' => 1100, 'requires_shipping' => true],
                'inventory' => $deny(15),
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'status' => ProductStatus::Active,
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => ['sale'],
                'description' => 'Sturdy aluminum monitor stand with cable management.',
                'published_at' => fn () => now(),
                'code' => 'MSTAND',
                'options' => [],
                'defaults' => [],
                'inventory' => $deny(30),
                'single_variant' => [
                    'sku' => 'ACME-MSTAND',
                    'price_amount' => 4999,
                    'currency' => 'EUR',
                    'weight_g' => 2500,
                    'requires_shipping' => true,
                    'inventory' => $deny(30),
                ],
            ],
        ];
    }

    /**
     * Build explicit cartesian variant overrides with a value => SKU-token map.
     *
     * @param  list<list<string>>  $valueSets
     * @param  array<string, mixed>  $inventory
     * @param  array<string, string>  $skuTokens
     * @return list<array<string, mixed>>
     */
    private function cartesianVariants(string $code, array $valueSets, array $inventory, array $skuTokens = []): array
    {
        $variants = [];

        foreach ($this->cartesian($valueSets) as $combo) {
            $tokens = array_map(
                fn (string $value): string => $skuTokens[$value] ?? trim(strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '-', $value)), '-'),
                $combo,
            );

            $variants[] = [
                'option_values' => $combo,
                'sku' => 'ACME-'.$code.'-'.implode('-', $tokens),
                'inventory' => $inventory,
            ];
        }

        return $variants;
    }
}
