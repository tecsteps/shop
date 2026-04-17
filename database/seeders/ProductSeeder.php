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
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedFashionProducts();
            $this->seedElectronicsProducts();
        });
    }

    private function seedFashionProducts(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();
        $storeId = $store->id;

        // Load collections for assignments
        $collections = Collection::where('store_id', $storeId)->get()->keyBy('handle');

        $products = $this->getFashionProductDefinitions();

        foreach ($products as $def) {
            $product = Product::firstOrCreate(
                ['store_id' => $storeId, 'handle' => $def['handle']],
                [
                    'title' => $def['title'],
                    'status' => $def['status'],
                    'description_html' => $def['description_html'],
                    'vendor' => $def['vendor'],
                    'product_type' => $def['product_type'],
                    'tags' => $def['tags'],
                    'published_at' => $def['published_at'],
                ],
            );

            // Create options and values
            $optionValueIds = $this->createOptionsAndValues($product, $def['options']);

            // Create variants
            $this->createVariants($product, $storeId, $def, $optionValueIds);

            // Assign to collections
            if (! empty($def['collections'])) {
                foreach ($def['collections'] as $position => $collectionHandle) {
                    $collection = $collections->get($collectionHandle);
                    if ($collection) {
                        DB::table('collection_products')->insertOrIgnore([
                            'collection_id' => $collection->id,
                            'product_id' => $product->id,
                            'position' => $position,
                        ]);
                    }
                }
            }
        }
    }

    private function seedElectronicsProducts(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();
        $storeId = $store->id;

        $collections = Collection::where('store_id', $storeId)->get()->keyBy('handle');

        $products = $this->getElectronicsProductDefinitions();

        foreach ($products as $def) {
            $product = Product::firstOrCreate(
                ['store_id' => $storeId, 'handle' => $def['handle']],
                [
                    'title' => $def['title'],
                    'status' => 'active',
                    'description_html' => $def['description_html'],
                    'vendor' => $def['vendor'],
                    'product_type' => $def['product_type'],
                    'tags' => $def['tags'],
                    'published_at' => now(),
                ],
            );

            $optionValueIds = $this->createOptionsAndValues($product, $def['options']);
            $this->createVariants($product, $storeId, $def, $optionValueIds);

            if (! empty($def['collections'])) {
                foreach ($def['collections'] as $position => $collectionHandle) {
                    $collection = $collections->get($collectionHandle);
                    if ($collection) {
                        DB::table('collection_products')->insertOrIgnore([
                            'collection_id' => $collection->id,
                            'product_id' => $product->id,
                            'position' => $position,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Create options and option values for a product.
     *
     * @return array<string, array<string, int>> Map of option_name => [value => id]
     */
    private function createOptionsAndValues(Product $product, array $options): array
    {
        $optionValueIds = [];

        foreach ($options as $position => $optionDef) {
            $option = ProductOption::firstOrCreate(
                ['product_id' => $product->id, 'name' => $optionDef['name']],
                ['position' => $position],
            );

            $optionValueIds[$optionDef['name']] = [];
            foreach ($optionDef['values'] as $valPos => $value) {
                $ov = ProductOptionValue::firstOrCreate(
                    ['product_option_id' => $option->id, 'value' => $value],
                    ['position' => $valPos],
                );
                $optionValueIds[$optionDef['name']][$value] = $ov->id;
            }
        }

        return $optionValueIds;
    }

    /**
     * Create variants for a product, link option values, and create inventory items.
     */
    private function createVariants(Product $product, int $storeId, array $def, array $optionValueIds): void
    {
        $variantDefs = $def['variant_builder']($optionValueIds);

        foreach ($variantDefs as $position => $vDef) {
            $variant = ProductVariant::firstOrCreate(
                ['product_id' => $product->id, 'sku' => $vDef['sku']],
                [
                    'price_amount' => $vDef['price_amount'],
                    'compare_at_amount' => $vDef['compare_at_amount'] ?? null,
                    'currency' => 'EUR',
                    'weight_g' => $vDef['weight_g'],
                    'requires_shipping' => $vDef['requires_shipping'] ?? true,
                    'is_default' => $vDef['is_default'] ?? false,
                    'position' => $position,
                    'status' => 'active',
                ],
            );

            // Link variant to option values via pivot
            if (! empty($vDef['option_value_ids'])) {
                foreach ($vDef['option_value_ids'] as $ovId) {
                    DB::table('variant_option_values')->insertOrIgnore([
                        'variant_id' => $variant->id,
                        'product_option_value_id' => $ovId,
                    ]);
                }
            }

            // Create inventory item
            InventoryItem::firstOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $storeId,
                    'quantity_on_hand' => $vDef['inventory'],
                    'quantity_reserved' => 0,
                    'policy' => $vDef['policy'] ?? 'deny',
                ],
            );
        }
    }

    /**
     * Generate SKU color abbreviation.
     */
    private static function colorAbbrev(string $color): string
    {
        return match ($color) {
            'White' => 'WHT',
            'Black' => 'BLK',
            'Navy' => 'NVY',
            'Blue' => 'BLU',
            'Brown' => 'BRN',
            'Beige' => 'BEI',
            'Olive' => 'OLV',
            'Sky Blue' => 'SKY',
            'Khaki' => 'KHK',
            'Sand' => 'SND',
            'Grey' => 'GRY',
            'Burgundy' => 'BRG',
            'Natural' => 'NAT',
            'Camel' => 'CML',
            'Charcoal' => 'CHR',
            'Silver' => 'SLV',
            'Red' => 'RED',
            default => strtoupper(substr($color, 0, 3)),
        };
    }

    /**
     * Generate SKU size abbreviation.
     */
    private static function sizeAbbrev(string $size): string
    {
        return match ($size) {
            'S/M' => 'SM',
            'L/XL' => 'LXL',
            default => strtoupper(str_replace(['EU ', ' '], ['EU', ''], $size)),
        };
    }

    /**
     * Build cross-product variant definitions from options.
     *
     * @return list<array{sku: string, price_amount: int, compare_at_amount: int|null, weight_g: int, requires_shipping: bool, is_default: bool, inventory: int, policy: string, option_value_ids: list<int>}>
     */
    private static function buildCrossVariants(
        string $skuPrefix,
        array $optionValueIds,
        int $price,
        ?int $compareAt,
        int $weight,
        int $inventory,
        string $policy = 'deny',
        bool $requiresShipping = true,
    ): array {
        $optionNames = array_keys($optionValueIds);
        $variants = [];
        $isFirst = true;

        if (count($optionNames) === 1) {
            $optName = $optionNames[0];
            foreach ($optionValueIds[$optName] as $value => $ovId) {
                $sku = $skuPrefix.'-'.self::sizeAbbrev((string) $value);
                $variants[] = [
                    'sku' => $sku,
                    'price_amount' => $price,
                    'compare_at_amount' => $compareAt,
                    'weight_g' => $weight,
                    'requires_shipping' => $requiresShipping,
                    'is_default' => $isFirst,
                    'inventory' => $inventory,
                    'policy' => $policy,
                    'option_value_ids' => [$ovId],
                ];
                $isFirst = false;
            }
        } elseif (count($optionNames) === 2) {
            $opt1 = $optionNames[0];
            $opt2 = $optionNames[1];
            foreach ($optionValueIds[$opt1] as $val1 => $ovId1) {
                foreach ($optionValueIds[$opt2] as $val2 => $ovId2) {
                    $sku = $skuPrefix.'-'.self::sizeAbbrev((string) $val1).'-'.self::colorAbbrev((string) $val2);
                    $variants[] = [
                        'sku' => $sku,
                        'price_amount' => $price,
                        'compare_at_amount' => $compareAt,
                        'weight_g' => $weight,
                        'requires_shipping' => $requiresShipping,
                        'is_default' => $isFirst,
                        'inventory' => $inventory,
                        'policy' => $policy,
                        'option_value_ids' => [$ovId1, $ovId2],
                    ];
                    $isFirst = false;
                }
            }
        }

        return $variants;
    }

    /**
     * @return list<array>
     */
    private function getFashionProductDefinitions(): array
    {
        return [
            // Product 1: Classic Cotton T-Shirt
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'description_html' => '<p>A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.</p>',
                'published_at' => now(),
                'collections' => ['new-arrivals', 't-shirts'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-CTSH', $ovIds, 2499, null, 200, 15),
            ],
            // Product 2: Premium Slim Fit Jeans
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description_html' => '<p>Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.</p>',
                'published_at' => now(),
                'collections' => ['new-arrivals', 'pants-jeans', 'sale'],
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-JEANS', $ovIds, 7999, 9999, 800, 8),
            ],
            // Product 3: Organic Hoodie
            [
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Made from 100% organic cotton. Warm, soft, and sustainably produced.</p>',
                'published_at' => now(),
                'collections' => ['new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-HOOD', $ovIds, 5999, null, 500, 20),
            ],
            // Product 4: Leather Belt
            [
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description_html' => '<p>Genuine leather belt with brushed metal buckle. A wardrobe essential.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-BELT', $ovIds, 3499, null, 150, 25),
            ],
            // Product 5: Running Sneakers
            [
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'status' => 'active',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description_html' => '<p>Lightweight running sneakers with responsive cushioning and breathable mesh upper.</p>',
                'published_at' => now(),
                'collections' => ['new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-SNKR', $ovIds, 11999, null, 600, 5),
            ],
            // Product 6: Graphic Print Tee
            [
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description_html' => '<p>Bold graphic print on soft cotton. Express yourself with this statement piece.</p>',
                'published_at' => now(),
                'collections' => ['t-shirts'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-GRPH', $ovIds, 2999, null, 210, 18),
            ],
            // Product 7: V-Neck Linen Tee
            [
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description_html' => '<p>Lightweight linen blend v-neck. Perfect for warm summer days.</p>',
                'published_at' => now(),
                'collections' => ['t-shirts'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-VNCK', $ovIds, 3499, null, 180, 12),
            ],
            // Product 8: Striped Polo Shirt
            [
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description_html' => '<p>Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.</p>',
                'published_at' => now(),
                'collections' => ['t-shirts', 'sale'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-POLO', $ovIds, 2799, 3999, 250, 10),
            ],
            // Product 9: Cargo Pants
            [
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'status' => 'active',
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description_html' => '<p>Utility cargo pants with multiple pockets. Durable cotton twill construction.</p>',
                'published_at' => now(),
                'collections' => ['pants-jeans'],
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-CARG', $ovIds, 5499, null, 700, 14),
            ],
            // Product 10: Chino Shorts
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.</p>',
                'published_at' => now(),
                'collections' => ['pants-jeans', 'new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-CHNO', $ovIds, 3999, null, 350, 16),
            ],
            // Product 11: Wide Leg Trousers
            [
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description_html' => '<p>Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.</p>',
                'published_at' => now(),
                'collections' => ['pants-jeans', 'sale'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-WLEG', $ovIds, 4999, 6999, 550, 7),
            ],
            // Product 12: Wool Scarf
            [
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description_html' => '<p>Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => [
                    ['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildColorOnlyVariants('ACME-SCRF', $ovIds, 2999, null, 120, 30),
            ],
            // Product 13: Canvas Tote Bag
            [
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description_html' => '<p>Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => [
                    ['name' => 'Color', 'values' => ['Natural', 'Black']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildColorOnlyVariants('ACME-TOTE', $ovIds, 1999, null, 300, 40),
            ],
            // Product 14: Bucket Hat
            [
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Lightweight bucket hat for sun protection. Packable design, washed cotton twill.</p>',
                'published_at' => now(),
                'collections' => ['new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-BHAT', $ovIds, 2499, null, 80, 22),
            ],
            // Product 15: Unreleased Winter Jacket (DRAFT)
            [
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'status' => 'draft',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'description_html' => '<p>Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.</p>',
                'published_at' => null,
                'collections' => [],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-WJKT', $ovIds, 14999, null, 900, 0),
            ],
            // Product 16: Discontinued Raincoat (ARCHIVED)
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => 'archived',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description_html' => '<p>Lightweight waterproof raincoat. This product has been discontinued.</p>',
                'published_at' => now()->subMonths(6),
                'collections' => [],
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-RAIN', $ovIds, 8999, null, 400, 3),
            ],
            // Product 17: Limited Edition Sneakers (SOLD OUT)
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'status' => 'active',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description_html' => '<p>Limited edition collaboration sneakers. Once they are gone, they are gone.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-LTDS', $ovIds, 15999, null, 650, 0, 'deny'),
            ],
            // Product 18: Backorder Denim Jacket (CONTINUE policy)
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description_html' => '<p>Classic denim jacket. Currently on backorder - ships within 2-3 weeks.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-BDJK', $ovIds, 9999, null, 750, 0, 'continue'),
            ],
            // Product 19: Gift Card (DIGITAL)
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'status' => 'active',
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description_html' => '<p>Digital gift card delivered via email. The perfect gift when you are not sure what to choose.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => [
                    ['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']],
                ],
                'variant_builder' => function ($ovIds) {
                    $denominations = [
                        '25 EUR' => ['sku' => 'ACME-GIFT-25', 'price' => 2500],
                        '50 EUR' => ['sku' => 'ACME-GIFT-50', 'price' => 5000],
                        '100 EUR' => ['sku' => 'ACME-GIFT-100', 'price' => 10000],
                    ];

                    $variants = [];
                    $isFirst = true;
                    foreach ($ovIds['Amount'] as $value => $ovId) {
                        $denom = $denominations[$value];
                        $variants[] = [
                            'sku' => $denom['sku'],
                            'price_amount' => $denom['price'],
                            'compare_at_amount' => null,
                            'weight_g' => 0,
                            'requires_shipping' => false,
                            'is_default' => $isFirst,
                            'inventory' => 9999,
                            'policy' => 'deny',
                            'option_value_ids' => [$ovId],
                        ];
                        $isFirst = false;
                    }

                    return $variants;
                },
            ],
            // Product 20: Cashmere Overcoat (EXPENSIVE)
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'status' => 'active',
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description_html' => '<p>Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.</p>',
                'published_at' => now(),
                'collections' => ['new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Camel', 'Charcoal']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildCrossVariants('ACME-CASH', $ovIds, 49999, null, 1200, 3),
            ],
        ];
    }

    /**
     * @return list<array>
     */
    private function getElectronicsProductDefinitions(): array
    {
        return [
            // E1: Pro Laptop 15
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['featured', 'new'],
                'description_html' => '<p>High-performance 15-inch laptop for professionals. Fast processor, stunning display.</p>',
                'collections' => ['featured'],
                'options' => [
                    ['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']],
                ],
                'variant_builder' => function ($ovIds) {
                    $prices = ['256GB' => 99999, '512GB' => 119999, '1TB' => 149999];
                    $variants = [];
                    $isFirst = true;
                    foreach ($ovIds['Storage'] as $value => $ovId) {
                        $variants[] = [
                            'sku' => 'ELEC-LAP-'.str_replace('GB', '', str_replace('TB', '000', $value)),
                            'price_amount' => $prices[$value],
                            'compare_at_amount' => null,
                            'weight_g' => 1800,
                            'requires_shipping' => true,
                            'is_default' => $isFirst,
                            'inventory' => 10,
                            'policy' => 'deny',
                            'option_value_ids' => [$ovId],
                        ];
                        $isFirst = false;
                    }

                    return $variants;
                },
            ],
            // E2: Wireless Headphones
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['featured', 'popular'],
                'description_html' => '<p>Premium wireless headphones with active noise cancellation and 30-hour battery life.</p>',
                'collections' => ['featured'],
                'options' => [
                    ['name' => 'Color', 'values' => ['Black', 'Silver']],
                ],
                'variant_builder' => fn ($ovIds) => self::buildColorOnlyVariants('ELEC-HEAD', $ovIds, 14999, null, 250, 25),
            ],
            // E3: USB-C Cable 2m (single default variant)
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => ['accessory'],
                'description_html' => '<p>Durable USB-C to USB-C cable, 2 meters. Supports fast charging and data transfer.</p>',
                'collections' => ['accessories'],
                'options' => [],
                'variant_builder' => function ($ovIds) {
                    return [
                        [
                            'sku' => 'ELEC-USBC-2M',
                            'price_amount' => 1299,
                            'compare_at_amount' => null,
                            'weight_g' => 50,
                            'requires_shipping' => true,
                            'is_default' => true,
                            'inventory' => 200,
                            'policy' => 'deny',
                            'option_value_ids' => [],
                        ],
                    ];
                },
            ],
            // E4: Mechanical Keyboard
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['featured', 'popular'],
                'description_html' => '<p>Full-size mechanical keyboard with customizable RGB lighting and hot-swappable switches.</p>',
                'collections' => ['featured'],
                'options' => [
                    ['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']],
                ],
                'variant_builder' => function ($ovIds) {
                    $variants = [];
                    $isFirst = true;
                    foreach ($ovIds['Switch Type'] as $value => $ovId) {
                        $variants[] = [
                            'sku' => 'ELEC-KEYB-'.strtoupper(substr($value, 0, 3)),
                            'price_amount' => 12999,
                            'compare_at_amount' => null,
                            'weight_g' => 1100,
                            'requires_shipping' => true,
                            'is_default' => $isFirst,
                            'inventory' => 15,
                            'policy' => 'deny',
                            'option_value_ids' => [$ovId],
                        ];
                        $isFirst = false;
                    }

                    return $variants;
                },
            ],
            // E5: Monitor Stand (single default variant)
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => ['accessory'],
                'description_html' => '<p>Adjustable monitor stand with cable management. Supports monitors up to 32 inches.</p>',
                'collections' => ['accessories'],
                'options' => [],
                'variant_builder' => function ($ovIds) {
                    return [
                        [
                            'sku' => 'ELEC-MSTAND',
                            'price_amount' => 4999,
                            'compare_at_amount' => null,
                            'weight_g' => 2500,
                            'requires_shipping' => true,
                            'is_default' => true,
                            'inventory' => 30,
                            'policy' => 'deny',
                            'option_value_ids' => [],
                        ],
                    ];
                },
            ],
        ];
    }

    /**
     * Build variants for products with only a Color option.
     */
    private static function buildColorOnlyVariants(
        string $skuPrefix,
        array $optionValueIds,
        int $price,
        ?int $compareAt,
        int $weight,
        int $inventory,
    ): array {
        $colorOption = reset($optionValueIds);
        $variants = [];
        $isFirst = true;

        foreach ($colorOption as $value => $ovId) {
            $sku = $skuPrefix.'-'.self::colorAbbrev((string) $value);
            $variants[] = [
                'sku' => $sku,
                'price_amount' => $price,
                'compare_at_amount' => $compareAt,
                'weight_g' => $weight,
                'requires_shipping' => true,
                'is_default' => $isFirst,
                'inventory' => $inventory,
                'policy' => 'deny',
                'option_value_ids' => [$ovId],
            ];
            $isFirst = false;
        }

        return $variants;
    }
}
