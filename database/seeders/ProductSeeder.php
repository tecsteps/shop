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
    /**
     * Seed products with options, variants, inventory, and collection assignments.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedAcmeFashion();
            $this->seedAcmeElectronics();
        });
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $products = $this->fashionProductDefinitions();

        foreach ($products as $index => $def) {
            $product = Product::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $def['handle']],
                [
                    'title' => $def['title'],
                    'status' => $def['status'],
                    'description_html' => '<p>'.$def['description'].'</p>',
                    'vendor' => $def['vendor'],
                    'product_type' => $def['product_type'],
                    'tags' => $def['tags'],
                    'published_at' => $def['published_at'],
                ],
            );

            $this->createOptionsAndVariants($store, $product, $def);

            if (! empty($def['collections'])) {
                foreach ($def['collections'] as $position => $collectionHandle) {
                    $collectionId = $collections[$collectionHandle] ?? null;
                    if ($collectionId) {
                        DB::table('collection_products')->updateOrInsert(
                            ['collection_id' => $collectionId, 'product_id' => $product->id],
                            ['position' => $position],
                        );
                    }
                }
            }
        }

        $this->fixCollectionPositions($collections);
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();

        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $products = $this->electronicsProductDefinitions();

        foreach ($products as $def) {
            $product = Product::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $def['handle']],
                [
                    'title' => $def['title'],
                    'status' => 'active',
                    'description_html' => '<p>'.$def['description'].'</p>',
                    'vendor' => $def['vendor'],
                    'product_type' => $def['product_type'],
                    'tags' => $def['tags'],
                    'published_at' => now(),
                ],
            );

            $this->createOptionsAndVariants($store, $product, $def);

            if (! empty($def['collections'])) {
                foreach ($def['collections'] as $collectionHandle) {
                    $collectionId = $collections[$collectionHandle] ?? null;
                    if ($collectionId) {
                        $maxPos = DB::table('collection_products')
                            ->where('collection_id', $collectionId)
                            ->max('position') ?? -1;

                        DB::table('collection_products')->updateOrInsert(
                            ['collection_id' => $collectionId, 'product_id' => $product->id],
                            ['position' => $maxPos + 1],
                        );
                    }
                }
            }
        }
    }

    /**
     * Create product options, option values, variants, variant-option-value pivots, and inventory.
     *
     * @param  array<string, mixed>  $def
     */
    private function createOptionsAndVariants(Store $store, Product $product, array $def): void
    {
        if ($product->variants()->exists()) {
            return;
        }

        $optionValueIds = [];

        foreach ($def['options'] as $optionPosition => $optionDef) {
            $option = ProductOption::firstOrCreate(
                ['product_id' => $product->id, 'name' => $optionDef['name']],
                ['position' => $optionPosition],
            );

            $optionValueIds[$optionDef['name']] = [];

            foreach ($optionDef['values'] as $valuePosition => $valueName) {
                $optionValue = ProductOptionValue::firstOrCreate(
                    ['product_option_id' => $option->id, 'value' => $valueName],
                    ['position' => $valuePosition],
                );
                $optionValueIds[$optionDef['name']][$valueName] = $optionValue->id;
            }
        }

        $variantCombinations = $this->buildVariantCombinations($def['options']);

        foreach ($variantCombinations as $position => $combo) {
            $sku = $this->buildSku($def, $combo);

            $priceAmount = $def['price_amount'];
            if (isset($def['variant_prices']) && isset($def['variant_prices'][$position])) {
                $priceAmount = $def['variant_prices'][$position];
            }

            $variant = ProductVariant::firstOrCreate(
                ['product_id' => $product->id, 'sku' => $sku],
                [
                    'barcode' => null,
                    'price_amount' => $priceAmount,
                    'compare_at_amount' => $def['compare_at_amount'] ?? null,
                    'currency' => 'EUR',
                    'weight_g' => $def['weight_g'],
                    'requires_shipping' => $def['requires_shipping'] ?? true,
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ],
            );

            foreach ($combo as $optionName => $valueName) {
                $optionValueId = $optionValueIds[$optionName][$valueName] ?? null;
                if ($optionValueId) {
                    DB::table('variant_option_values')->insertOrIgnore([
                        'variant_id' => $variant->id,
                        'product_option_value_id' => $optionValueId,
                    ]);
                }
            }

            InventoryItem::withoutGlobalScopes()->firstOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => $def['inventory'],
                    'quantity_reserved' => 0,
                    'policy' => $def['inventory_policy'] ?? 'deny',
                ],
            );
        }
    }

    /**
     * Build all variant combinations from option definitions.
     *
     * @param  array<int, array{name: string, values: array<int, string>}>  $options
     * @return array<int, array<string, string>>
     */
    private function buildVariantCombinations(array $options): array
    {
        if (empty($options)) {
            return [[]];
        }

        $result = [[]];

        foreach ($options as $optionDef) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($optionDef['values'] as $value) {
                    $newResult[] = array_merge($existing, [$optionDef['name'] => $value]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    /**
     * Build a SKU from the product definition and variant combination.
     *
     * @param  array<string, mixed>  $def
     * @param  array<string, string>  $combo
     */
    private function buildSku(array $def, array $combo): string
    {
        if (isset($def['sku_pattern'])) {
            return $def['sku_pattern'];
        }

        if (isset($def['sku_map'])) {
            $key = implode('/', array_values($combo));

            return $def['sku_map'][$key] ?? $def['sku_prefix'].'-'.strtoupper(str_replace([' ', '/'], ['', '-'], implode('-', array_values($combo))));
        }

        $prefix = $def['sku_prefix'] ?? 'ACME';
        $parts = [$prefix];

        $abbreviations = [
            'White' => 'WHT', 'Black' => 'BLK', 'Navy' => 'NVY',
            'Blue' => 'BLU', 'Brown' => 'BRN', 'Grey' => 'GRY',
            'Burgundy' => 'BRG', 'Beige' => 'BGE', 'Olive' => 'OLV',
            'Sky Blue' => 'SKY', 'Khaki' => 'KHK', 'Sand' => 'SND',
            'Natural' => 'NAT', 'Camel' => 'CML', 'Charcoal' => 'CHR',
            'Red' => 'RED', 'Silver' => 'SLV',
        ];

        foreach ($combo as $value) {
            $parts[] = $abbreviations[$value] ?? strtoupper(str_replace([' ', '/'], ['', '-'], $value));
        }

        return implode('-', $parts);
    }

    /**
     * Reassign collection positions to match the spec ordering.
     *
     * @param  \Illuminate\Support\Collection<string, int>  $collections
     */
    private function fixCollectionPositions(\Illuminate\Support\Collection $collections): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $collectionAssignments = [
            'new-arrivals' => ['classic-cotton-t-shirt', 'premium-slim-fit-jeans', 'organic-hoodie', 'running-sneakers', 'chino-shorts', 'bucket-hat', 'cashmere-overcoat'],
            't-shirts' => ['classic-cotton-t-shirt', 'graphic-print-tee', 'v-neck-linen-tee', 'striped-polo-shirt'],
            'pants-jeans' => ['premium-slim-fit-jeans', 'cargo-pants', 'chino-shorts', 'wide-leg-trousers'],
            'sale' => ['premium-slim-fit-jeans', 'striped-polo-shirt', 'wide-leg-trousers'],
        ];

        foreach ($collectionAssignments as $collHandle => $productHandles) {
            $collectionId = $collections[$collHandle] ?? null;
            if (! $collectionId) {
                continue;
            }

            DB::table('collection_products')->where('collection_id', $collectionId)->delete();

            foreach ($productHandles as $position => $productHandle) {
                $product = Product::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('handle', $productHandle)
                    ->first();

                if ($product) {
                    DB::table('collection_products')->insert([
                        'collection_id' => $collectionId,
                        'product_id' => $product->id,
                        'position' => $position,
                    ]);
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fashionProductDefinitions(): array
    {
        return [
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'description' => 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
                'sku_prefix' => 'ACME-CTSH',
                'price_amount' => 2499,
                'weight_g' => 200,
                'inventory' => 15,
                'collections' => ['new-arrivals', 't-shirts'],
            ],
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description' => 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
                'sku_prefix' => 'ACME-JEANS',
                'price_amount' => 7999,
                'compare_at_amount' => 9999,
                'weight_g' => 800,
                'inventory' => 8,
                'collections' => ['new-arrivals', 'pants-jeans', 'sale'],
            ],
            [
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description' => 'Made from 100% organic cotton. Warm, soft, and sustainably produced.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'sku_prefix' => 'ACME-HOOD',
                'price_amount' => 5999,
                'weight_g' => 500,
                'inventory' => 20,
                'collections' => ['new-arrivals'],
            ],
            [
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Genuine leather belt with brushed metal buckle. A wardrobe essential.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
                'sku_prefix' => 'ACME-BELT',
                'price_amount' => 3499,
                'weight_g' => 150,
                'inventory' => 25,
                'collections' => [],
            ],
            [
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'status' => 'active',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description' => 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
                'sku_prefix' => 'ACME-SNKR',
                'price_amount' => 11999,
                'weight_g' => 600,
                'inventory' => 5,
                'collections' => ['new-arrivals'],
            ],
            [
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description' => 'Bold graphic print on soft cotton. Express yourself with this statement piece.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'sku_prefix' => 'ACME-GRPH',
                'price_amount' => 2999,
                'weight_g' => 210,
                'inventory' => 18,
                'collections' => ['t-shirts'],
            ],
            [
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description' => 'Lightweight linen blend v-neck. Perfect for warm summer days.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
                'sku_prefix' => 'ACME-VNCK',
                'price_amount' => 3499,
                'weight_g' => 180,
                'inventory' => 12,
                'collections' => ['t-shirts'],
            ],
            [
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description' => 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'sku_prefix' => 'ACME-POLO',
                'price_amount' => 2799,
                'compare_at_amount' => 3999,
                'weight_g' => 250,
                'inventory' => 10,
                'collections' => ['t-shirts', 'sale'],
            ],
            [
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'status' => 'active',
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description' => 'Utility cargo pants with multiple pockets. Durable cotton twill construction.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
                'sku_prefix' => 'ACME-CRGO',
                'price_amount' => 5499,
                'weight_g' => 700,
                'inventory' => 14,
                'collections' => ['pants-jeans'],
            ],
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description' => 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
                'sku_prefix' => 'ACME-CHNO',
                'price_amount' => 3999,
                'weight_g' => 350,
                'inventory' => 16,
                'collections' => ['pants-jeans', 'new-arrivals'],
            ],
            [
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description' => 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ],
                'sku_prefix' => 'ACME-WLEG',
                'price_amount' => 4999,
                'compare_at_amount' => 6999,
                'weight_g' => 550,
                'inventory' => 7,
                'collections' => ['pants-jeans', 'sale'],
            ],
            [
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']],
                ],
                'sku_prefix' => 'ACME-SCRF',
                'price_amount' => 2999,
                'weight_g' => 120,
                'inventory' => 30,
                'collections' => [],
            ],
            [
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description' => 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Color', 'values' => ['Natural', 'Black']],
                ],
                'sku_prefix' => 'ACME-TOTE',
                'price_amount' => 1999,
                'weight_g' => 300,
                'inventory' => 40,
                'collections' => [],
            ],
            [
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description' => 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
                'sku_prefix' => 'ACME-BHAT',
                'price_amount' => 2499,
                'weight_g' => 80,
                'inventory' => 22,
                'collections' => ['new-arrivals'],
            ],
            [
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'status' => 'draft',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'description' => 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.',
                'published_at' => null,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'sku_prefix' => 'ACME-WJKT',
                'price_amount' => 14999,
                'weight_g' => 900,
                'inventory' => 0,
                'collections' => [],
            ],
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => 'archived',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description' => 'Lightweight waterproof raincoat. This product has been discontinued.',
                'published_at' => now()->subMonths(6),
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
                'sku_prefix' => 'ACME-RAIN',
                'price_amount' => 8999,
                'weight_g' => 400,
                'inventory' => 3,
                'collections' => [],
            ],
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'status' => 'active',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description' => 'Limited edition collaboration sneakers. Once they are gone, they are gone.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']],
                ],
                'sku_prefix' => 'ACME-LSNK',
                'price_amount' => 15999,
                'weight_g' => 650,
                'inventory' => 0,
                'inventory_policy' => 'deny',
                'collections' => [],
            ],
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description' => 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'sku_prefix' => 'ACME-DJKT',
                'price_amount' => 9999,
                'weight_g' => 750,
                'inventory' => 0,
                'inventory_policy' => 'continue',
                'collections' => [],
            ],
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'status' => 'active',
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description' => 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']],
                ],
                'sku_map' => [
                    '25 EUR' => 'ACME-GIFT-25',
                    '50 EUR' => 'ACME-GIFT-50',
                    '100 EUR' => 'ACME-GIFT-100',
                ],
                'variant_prices' => [0 => 2500, 1 => 5000, 2 => 10000],
                'price_amount' => 2500,
                'weight_g' => 0,
                'requires_shipping' => false,
                'inventory' => 9999,
                'collections' => [],
            ],
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'status' => 'active',
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description' => 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Camel', 'Charcoal']],
                ],
                'sku_prefix' => 'ACME-CASH',
                'price_amount' => 49999,
                'weight_g' => 1200,
                'inventory' => 3,
                'collections' => ['new-arrivals'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function electronicsProductDefinitions(): array
    {
        return [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['popular', 'new'],
                'description' => 'Professional-grade 15-inch laptop with high-resolution display and all-day battery life.',
                'options' => [
                    ['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']],
                ],
                'sku_prefix' => 'ELEC-LPTP',
                'variant_prices' => [0 => 99999, 1 => 119999, 2 => 149999],
                'price_amount' => 99999,
                'weight_g' => 1800,
                'inventory' => 10,
                'collections' => ['featured'],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['popular'],
                'description' => 'Premium wireless headphones with active noise cancellation and 30-hour battery life.',
                'options' => [
                    ['name' => 'Color', 'values' => ['Black', 'Silver']],
                ],
                'sku_prefix' => 'ELEC-HDPH',
                'price_amount' => 14999,
                'weight_g' => 250,
                'inventory' => 25,
                'collections' => ['featured'],
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => ['accessories'],
                'description' => 'Durable USB-C cable with fast charging support. 2-meter length for convenience.',
                'options' => [],
                'sku_prefix' => 'ELEC-USBC',
                'sku_pattern' => 'ELEC-USBC-2M',
                'price_amount' => 1299,
                'weight_g' => 50,
                'inventory' => 200,
                'collections' => ['accessories'],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['popular', 'trending'],
                'description' => 'Full-size mechanical keyboard with customizable RGB lighting and hot-swappable switches.',
                'options' => [
                    ['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']],
                ],
                'sku_prefix' => 'ELEC-KBRD',
                'price_amount' => 12999,
                'weight_g' => 1100,
                'inventory' => 15,
                'collections' => ['featured'],
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => ['accessories'],
                'description' => 'Ergonomic monitor stand with adjustable height and cable management.',
                'options' => [],
                'sku_prefix' => 'ELEC-MSTD',
                'sku_pattern' => 'ELEC-MSTD-STD',
                'price_amount' => 4999,
                'weight_g' => 2500,
                'inventory' => 30,
                'collections' => ['accessories'],
            ],
        ];
    }
}
