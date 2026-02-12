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
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        $this->seedAcmeFashion($fashion);
        $this->seedAcmeElectronics($electronics);
    }

    private function seedAcmeFashion(Store $store): void
    {
        $products = $this->getFashionProducts();

        // Load collections for assignment
        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        foreach ($products as $productData) {
            $product = Product::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $productData['handle']],
                [
                    'title' => $productData['title'],
                    'status' => $productData['status'],
                    'description_html' => '<p>'.$productData['description'].'</p>',
                    'vendor' => $productData['vendor'],
                    'product_type' => $productData['product_type'],
                    'tags' => $productData['tags'],
                    'published_at' => $productData['published_at'],
                ],
            );

            // Create options and option values
            $optionValueMap = $this->createOptionsAndValues($product, $productData['options']);

            // Create variants with inventory
            $this->createVariants(
                $store,
                $product,
                $productData,
                $optionValueMap,
            );

            // Collection assignments
            if (! empty($productData['collections'])) {
                foreach ($productData['collections'] as $position => $collectionHandle) {
                    if (isset($collections[$collectionHandle])) {
                        DB::table('collection_products')->insertOrIgnore([
                            'collection_id' => $collections[$collectionHandle],
                            'product_id' => $product->id,
                            'position' => $position,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function createOptionsAndValues(Product $product, array $options): array
    {
        $optionValueMap = [];

        foreach ($options as $optionPosition => $optionData) {
            $option = ProductOption::firstOrCreate(
                ['product_id' => $product->id, 'name' => $optionData['name']],
                ['position' => $optionPosition],
            );

            foreach ($optionData['values'] as $valuePosition => $value) {
                $optionValue = ProductOptionValue::firstOrCreate(
                    ['product_option_id' => $option->id, 'value' => $value],
                    ['position' => $valuePosition],
                );
                $optionValueMap[$optionData['name']][$value] = $optionValue->id;
            }
        }

        return $optionValueMap;
    }

    private function createVariants(
        Store $store,
        Product $product,
        array $productData,
        array $optionValueMap,
    ): void {
        // Handle Gift Card with custom per-variant prices
        if (! empty($productData['custom_variants'])) {
            $this->createGiftCardVariants($store, $product, $productData, $optionValueMap);

            return;
        }

        // Generate variant combinations
        $combinations = $this->generateCombinations($productData['options']);

        foreach ($combinations as $position => $combo) {
            $skuSuffix = $this->buildSkuSuffix($combo);
            $sku = $productData['sku_prefix'].'-'.$skuSuffix;

            $variant = ProductVariant::firstOrCreate(
                ['product_id' => $product->id, 'sku' => $sku],
                [
                    'price_amount' => $productData['price_amount'],
                    'compare_at_amount' => $productData['compare_at_amount'] ?? null,
                    'currency' => 'EUR',
                    'weight_g' => $productData['weight_g'],
                    'requires_shipping' => $productData['requires_shipping'] ?? true,
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ],
            );

            // Wire variant to option values
            foreach ($combo as $optionName => $optionValue) {
                if (isset($optionValueMap[$optionName][$optionValue])) {
                    DB::table('variant_option_values')->insertOrIgnore([
                        'variant_id' => $variant->id,
                        'product_option_value_id' => $optionValueMap[$optionName][$optionValue],
                    ]);
                }
            }

            // Create inventory
            InventoryItem::withoutGlobalScopes()->firstOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => $productData['inventory_qty'],
                    'quantity_reserved' => 0,
                    'policy' => $productData['inventory_policy'] ?? 'deny',
                ],
            );
        }
    }

    private function createGiftCardVariants(
        Store $store,
        Product $product,
        array $productData,
        array $optionValueMap,
    ): void {
        $giftCardPrices = [
            '25 EUR' => 2500,
            '50 EUR' => 5000,
            '100 EUR' => 10000,
        ];

        $position = 0;
        foreach ($giftCardPrices as $label => $price) {
            $sku = 'ACME-GIFT-'.str_replace(' EUR', '', $label);

            $variant = ProductVariant::firstOrCreate(
                ['product_id' => $product->id, 'sku' => $sku],
                [
                    'price_amount' => $price,
                    'currency' => 'EUR',
                    'weight_g' => 0,
                    'requires_shipping' => false,
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ],
            );

            // Wire to option value
            if (isset($optionValueMap['Amount'][$label])) {
                DB::table('variant_option_values')->insertOrIgnore([
                    'variant_id' => $variant->id,
                    'product_option_value_id' => $optionValueMap['Amount'][$label],
                ]);
            }

            InventoryItem::withoutGlobalScopes()->firstOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => 9999,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ],
            );

            $position++;
        }
    }

    /**
     * @return list<array<string, string>>
     */
    private function generateCombinations(array $options): array
    {
        if (empty($options)) {
            return [[]];
        }

        $result = [[]];

        foreach ($options as $optionData) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($optionData['values'] as $value) {
                    $newResult[] = array_merge($existing, [$optionData['name'] => $value]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    private function buildSkuSuffix(array $combo): string
    {
        $parts = [];
        foreach ($combo as $value) {
            $parts[] = strtoupper(substr(str_replace(['/', ' '], '', $value), 0, 3));
        }

        return implode('-', $parts);
    }

    private function getFashionProducts(): array
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
                'sku_prefix' => 'ACME-CTSH',
                'price_amount' => 2499,
                'compare_at_amount' => null,
                'weight_g' => 200,
                'inventory_qty' => 15,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
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
                'sku_prefix' => 'ACME-JEANS',
                'price_amount' => 7999,
                'compare_at_amount' => 9999,
                'weight_g' => 800,
                'inventory_qty' => 8,
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
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
                'sku_prefix' => 'ACME-HOOD',
                'price_amount' => 5999,
                'compare_at_amount' => null,
                'weight_g' => 500,
                'inventory_qty' => 20,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
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
                'sku_prefix' => 'ACME-BELT',
                'price_amount' => 3499,
                'compare_at_amount' => null,
                'weight_g' => 150,
                'inventory_qty' => 25,
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
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
                'sku_prefix' => 'ACME-SNKR',
                'price_amount' => 11999,
                'compare_at_amount' => null,
                'weight_g' => 600,
                'inventory_qty' => 5,
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
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
                'sku_prefix' => 'ACME-GPTEE',
                'price_amount' => 2999,
                'compare_at_amount' => null,
                'weight_g' => 210,
                'inventory_qty' => 18,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
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
                'sku_prefix' => 'ACME-VNLT',
                'price_amount' => 3499,
                'compare_at_amount' => null,
                'weight_g' => 180,
                'inventory_qty' => 12,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
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
                'sku_prefix' => 'ACME-POLO',
                'price_amount' => 2799,
                'compare_at_amount' => 3999,
                'weight_g' => 250,
                'inventory_qty' => 10,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
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
                'sku_prefix' => 'ACME-CRGO',
                'price_amount' => 5499,
                'compare_at_amount' => null,
                'weight_g' => 700,
                'inventory_qty' => 14,
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
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
                'sku_prefix' => 'ACME-CHNO',
                'price_amount' => 3999,
                'compare_at_amount' => null,
                'weight_g' => 350,
                'inventory_qty' => 16,
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
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
                'sku_prefix' => 'ACME-WLTR',
                'price_amount' => 4999,
                'compare_at_amount' => 6999,
                'weight_g' => 550,
                'inventory_qty' => 7,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ],
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
                'sku_prefix' => 'ACME-WSCF',
                'price_amount' => 2999,
                'compare_at_amount' => null,
                'weight_g' => 120,
                'inventory_qty' => 30,
                'options' => [
                    ['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']],
                ],
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
                'sku_prefix' => 'ACME-TOTE',
                'price_amount' => 1999,
                'compare_at_amount' => null,
                'weight_g' => 300,
                'inventory_qty' => 40,
                'options' => [
                    ['name' => 'Color', 'values' => ['Natural', 'Black']],
                ],
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
                'sku_prefix' => 'ACME-BHAT',
                'price_amount' => 2499,
                'compare_at_amount' => null,
                'weight_g' => 80,
                'inventory_qty' => 22,
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
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
                'sku_prefix' => 'ACME-WJKT',
                'price_amount' => 14999,
                'compare_at_amount' => null,
                'weight_g' => 900,
                'inventory_qty' => 0,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
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
                'sku_prefix' => 'ACME-RAIN',
                'price_amount' => 8999,
                'compare_at_amount' => null,
                'weight_g' => 400,
                'inventory_qty' => 3,
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
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
                'sku_prefix' => 'ACME-LSNK',
                'price_amount' => 15999,
                'compare_at_amount' => null,
                'weight_g' => 650,
                'inventory_qty' => 0,
                'inventory_policy' => 'deny',
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']],
                ],
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
                'sku_prefix' => 'ACME-DJKT',
                'price_amount' => 9999,
                'compare_at_amount' => null,
                'weight_g' => 750,
                'inventory_qty' => 0,
                'inventory_policy' => 'continue',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
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
                'sku_prefix' => 'ACME-GIFT',
                'price_amount' => 0, // overridden per variant
                'compare_at_amount' => null,
                'weight_g' => 0,
                'requires_shipping' => false,
                'inventory_qty' => 9999,
                'options' => [
                    ['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']],
                ],
                'collections' => [],
                'custom_variants' => true,
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
                'sku_prefix' => 'ACME-CASH',
                'price_amount' => 49999,
                'compare_at_amount' => null,
                'weight_g' => 1200,
                'inventory_qty' => 3,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Camel', 'Charcoal']],
                ],
                'collections' => ['new-arrivals'],
            ],
        ];
    }

    private function seedAcmeElectronics(Store $store): void
    {
        $collections = Collection::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $products = [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['featured'],
                'description' => 'Professional-grade 15-inch laptop with high-performance specs.',
                'sku_prefix' => 'ELEC-LAP',
                'weight_g' => 1800,
                'inventory_qty' => 10,
                'options' => [
                    ['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']],
                ],
                'variant_prices' => [99999, 119999, 149999],
                'collections' => ['featured'],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['popular'],
                'description' => 'Premium wireless headphones with active noise cancellation.',
                'sku_prefix' => 'ELEC-HP',
                'weight_g' => 250,
                'inventory_qty' => 25,
                'options' => [
                    ['name' => 'Color', 'values' => ['Black', 'Silver']],
                ],
                'variant_prices' => [14999, 14999],
                'collections' => ['featured'],
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => ['essentials'],
                'description' => 'High-speed USB-C cable, 2 meters, braided nylon.',
                'sku_prefix' => 'ELEC-USBC',
                'weight_g' => 50,
                'inventory_qty' => 200,
                'options' => [],
                'variant_prices' => [1299],
                'collections' => ['accessories'],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['trending'],
                'description' => 'Full-size mechanical keyboard with customizable switches.',
                'sku_prefix' => 'ELEC-KB',
                'weight_g' => 1100,
                'inventory_qty' => 15,
                'options' => [
                    ['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']],
                ],
                'variant_prices' => [12999, 12999, 12999],
                'collections' => ['featured'],
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => ['office'],
                'description' => 'Adjustable monitor stand with cable management.',
                'sku_prefix' => 'ELEC-MSTD',
                'weight_g' => 2500,
                'inventory_qty' => 30,
                'options' => [],
                'variant_prices' => [4999],
                'collections' => ['accessories'],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $productData['handle']],
                [
                    'title' => $productData['title'],
                    'status' => 'active',
                    'description_html' => '<p>'.$productData['description'].'</p>',
                    'vendor' => $productData['vendor'],
                    'product_type' => $productData['product_type'],
                    'tags' => $productData['tags'],
                    'published_at' => now(),
                ],
            );

            $optionValueMap = $this->createOptionsAndValues($product, $productData['options']);

            // Create variants
            if (empty($productData['options'])) {
                // Single default variant
                $sku = $productData['sku_prefix'].'-DEF';
                $variant = ProductVariant::firstOrCreate(
                    ['product_id' => $product->id, 'sku' => $sku],
                    [
                        'price_amount' => $productData['variant_prices'][0],
                        'currency' => 'EUR',
                        'weight_g' => $productData['weight_g'],
                        'requires_shipping' => true,
                        'is_default' => true,
                        'position' => 0,
                        'status' => 'active',
                    ],
                );

                InventoryItem::withoutGlobalScopes()->firstOrCreate(
                    ['variant_id' => $variant->id],
                    [
                        'store_id' => $store->id,
                        'quantity_on_hand' => $productData['inventory_qty'],
                        'quantity_reserved' => 0,
                        'policy' => 'deny',
                    ],
                );
            } else {
                $combinations = $this->generateCombinations($productData['options']);

                foreach ($combinations as $position => $combo) {
                    $skuSuffix = $this->buildSkuSuffix($combo);
                    $sku = $productData['sku_prefix'].'-'.$skuSuffix;

                    $price = $productData['variant_prices'][$position] ?? $productData['variant_prices'][0];

                    $variant = ProductVariant::firstOrCreate(
                        ['product_id' => $product->id, 'sku' => $sku],
                        [
                            'price_amount' => $price,
                            'currency' => 'EUR',
                            'weight_g' => $productData['weight_g'],
                            'requires_shipping' => true,
                            'is_default' => $position === 0,
                            'position' => $position,
                            'status' => 'active',
                        ],
                    );

                    foreach ($combo as $optionName => $optionValue) {
                        if (isset($optionValueMap[$optionName][$optionValue])) {
                            DB::table('variant_option_values')->insertOrIgnore([
                                'variant_id' => $variant->id,
                                'product_option_value_id' => $optionValueMap[$optionName][$optionValue],
                            ]);
                        }
                    }

                    InventoryItem::withoutGlobalScopes()->firstOrCreate(
                        ['variant_id' => $variant->id],
                        [
                            'store_id' => $store->id,
                            'quantity_on_hand' => $productData['inventory_qty'],
                            'quantity_reserved' => 0,
                            'policy' => 'deny',
                        ],
                    );
                }
            }

            // Collection assignments
            foreach ($productData['collections'] as $position => $collectionHandle) {
                if (isset($collections[$collectionHandle])) {
                    DB::table('collection_products')->insertOrIgnore([
                        'collection_id' => $collections[$collectionHandle],
                        'product_id' => $product->id,
                        'position' => $position,
                    ]);
                }
            }
        }
    }
}
