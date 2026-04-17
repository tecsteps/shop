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
        $this->seedFashionProducts();
        $this->seedElectronicsProducts();
    }

    private function seedFashionProducts(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();
        app()->instance('current_store', $store);

        $collections = Collection::where('store_id', $store->id)->get()->keyBy('handle');

        $products = $this->getFashionProductData();

        foreach ($products as $data) {
            $product = Product::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $data['handle'],
                'status' => $data['status'],
                'description_html' => '<p>'.$data['description'].'</p>',
                'vendor' => $data['vendor'],
                'product_type' => $data['product_type'],
                'tags' => $data['tags'],
                'published_at' => $data['published_at'],
            ]);

            // Attach to collections
            foreach ($data['collections'] as $position => $handle) {
                if ($collections->has($handle)) {
                    $product->collections()->attach($collections[$handle]->id, ['position' => $position]);
                }
            }

            // Create options and variants
            $this->createOptionsAndVariants($product, $store, $data);
        }
    }

    private function seedElectronicsProducts(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();
        app()->instance('current_store', $store);

        $collections = Collection::where('store_id', $store->id)->get()->keyBy('handle');

        $products = [
            [
                'title' => 'Pro Laptop 15', 'handle' => 'pro-laptop-15', 'vendor' => 'TechCorp',
                'product_type' => 'Laptops', 'tags' => ['featured'], 'description' => 'Powerful 15-inch laptop for professionals.',
                'options' => ['Storage' => ['256GB', '512GB', '1TB']],
                'prices' => [99999, 119999, 149999], 'weight_g' => 1800, 'inventory' => 10,
                'collections' => ['featured'],
            ],
            [
                'title' => 'Wireless Headphones', 'handle' => 'wireless-headphones', 'vendor' => 'AudioMax',
                'product_type' => 'Audio', 'tags' => ['popular'], 'description' => 'Premium wireless headphones with noise cancellation.',
                'options' => ['Color' => ['Black', 'Silver']],
                'prices' => [14999, 14999], 'weight_g' => 250, 'inventory' => 25,
                'collections' => ['featured', 'accessories'],
            ],
            [
                'title' => 'USB-C Cable 2m', 'handle' => 'usb-c-cable-2m', 'vendor' => 'CablePro',
                'product_type' => 'Cables', 'tags' => [], 'description' => 'High-quality USB-C cable, 2 meters.',
                'options' => [], 'prices' => [1299], 'weight_g' => 50, 'inventory' => 200,
                'collections' => ['accessories'],
            ],
            [
                'title' => 'Mechanical Keyboard', 'handle' => 'mechanical-keyboard', 'vendor' => 'KeyTech',
                'product_type' => 'Peripherals', 'tags' => ['new'], 'description' => 'Full-size mechanical keyboard with RGB backlight.',
                'options' => ['Switch Type' => ['Red', 'Blue', 'Brown']],
                'prices' => [12999, 12999, 12999], 'weight_g' => 1100, 'inventory' => 15,
                'collections' => ['featured'],
            ],
            [
                'title' => 'Monitor Stand', 'handle' => 'monitor-stand', 'vendor' => 'DeskGear',
                'product_type' => 'Accessories', 'tags' => [], 'description' => 'Ergonomic monitor stand with cable management.',
                'options' => [], 'prices' => [4999], 'weight_g' => 2500, 'inventory' => 30,
                'collections' => ['accessories'],
            ],
        ];

        foreach ($products as $data) {
            $product = Product::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $data['handle'],
                'status' => 'active',
                'description_html' => '<p>'.$data['description'].'</p>',
                'vendor' => $data['vendor'],
                'product_type' => $data['product_type'],
                'tags' => $data['tags'],
                'published_at' => now(),
            ]);

            foreach ($data['collections'] as $position => $handle) {
                if ($collections->has($handle)) {
                    $product->collections()->attach($collections[$handle]->id, ['position' => $position]);
                }
            }

            $this->createElectronicsVariants($product, $store, $data);
        }
    }

    private function createOptionsAndVariants(Product $product, Store $store, array $data): void
    {
        $options = $data['options'] ?? [];
        $optionValueIds = [];

        foreach ($options as $position => $optionData) {
            $option = ProductOption::create([
                'product_id' => $product->id,
                'name' => $optionData['name'],
                'position' => $position,
            ]);

            $valueIds = [];
            foreach ($optionData['values'] as $vPos => $value) {
                $ov = ProductOptionValue::create([
                    'product_option_id' => $option->id,
                    'value' => $value,
                    'position' => $vPos,
                ]);
                $valueIds[$value] = $ov->id;
            }
            $optionValueIds[$optionData['name']] = $valueIds;
        }

        // Build variant combinations
        $combinations = $this->buildCombinations($options);
        $price = $data['price'];
        $compareAt = $data['compare_at'] ?? null;
        $weight = $data['weight_g'];
        $requiresShipping = $data['requires_shipping'] ?? true;
        $inventory = $data['inventory'];
        $inventoryPolicy = $data['inventory_policy'] ?? 'deny';
        $skuPrefix = $data['sku_prefix'] ?? 'ACME';

        foreach ($combinations as $position => $combo) {
            $skuParts = [$skuPrefix];
            $pivotValues = [];

            foreach ($combo as $optionName => $value) {
                $skuParts[] = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $value), 0, 3));
                if (isset($optionValueIds[$optionName][$value])) {
                    $pivotValues[] = $optionValueIds[$optionName][$value];
                }
            }

            $variantPrice = $price;
            if (isset($data['variant_prices']) && isset($data['variant_prices'][$position])) {
                $variantPrice = $data['variant_prices'][$position];
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => implode('-', $skuParts).'-'.($position + 1),
                'price_amount' => $variantPrice,
                'compare_at_amount' => $compareAt,
                'currency' => 'EUR',
                'weight_g' => $weight,
                'requires_shipping' => $requiresShipping,
                'is_default' => $position === 0,
                'position' => $position,
                'status' => 'active',
            ]);

            foreach ($pivotValues as $pvId) {
                DB::table('variant_option_values')->insert([
                    'variant_id' => $variant->id,
                    'product_option_value_id' => $pvId,
                ]);
            }

            InventoryItem::create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $inventory,
                'quantity_reserved' => 0,
                'policy' => $inventoryPolicy,
            ]);
        }
    }

    private function createElectronicsVariants(Product $product, Store $store, array $data): void
    {
        $optionValueIds = [];

        foreach ($data['options'] as $optName => $values) {
            $option = ProductOption::create([
                'product_id' => $product->id,
                'name' => $optName,
                'position' => 0,
            ]);

            $valueIds = [];
            foreach ($values as $vPos => $value) {
                $ov = ProductOptionValue::create([
                    'product_option_id' => $option->id,
                    'value' => $value,
                    'position' => $vPos,
                ]);
                $valueIds[$value] = $ov->id;
            }
            $optionValueIds[$optName] = $valueIds;
        }

        if (empty($data['options'])) {
            // Single default variant
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => 'ELEC-'.strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data['handle']), 0, 8)),
                'price_amount' => $data['prices'][0],
                'currency' => 'EUR',
                'weight_g' => $data['weight_g'],
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 0,
                'status' => 'active',
            ]);

            InventoryItem::create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $data['inventory'],
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ]);

            return;
        }

        $position = 0;
        foreach ($data['options'] as $optName => $values) {
            foreach ($values as $idx => $value) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => 'ELEC-'.strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $value), 0, 6)).'-'.$position,
                    'price_amount' => $data['prices'][$idx] ?? $data['prices'][0],
                    'currency' => 'EUR',
                    'weight_g' => $data['weight_g'],
                    'requires_shipping' => true,
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ]);

                if (isset($optionValueIds[$optName][$value])) {
                    DB::table('variant_option_values')->insert([
                        'variant_id' => $variant->id,
                        'product_option_value_id' => $optionValueIds[$optName][$value],
                    ]);
                }

                InventoryItem::create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => $data['inventory'],
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ]);

                $position++;
            }
        }
    }

    /** @return array<int, array<string, string>> */
    private function buildCombinations(array $options): array
    {
        if (empty($options)) {
            return [[]];
        }

        $result = [[]];

        foreach ($options as $optionData) {
            $newResult = [];
            foreach ($result as $combo) {
                foreach ($optionData['values'] as $value) {
                    $newResult[] = array_merge($combo, [$optionData['name'] => $value]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function getFashionProductData(): array
    {
        return [
            [
                'title' => 'Classic Cotton T-Shirt', 'handle' => 'classic-cotton-t-shirt',
                'status' => 'active', 'vendor' => 'Acme Basics', 'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'], 'published_at' => now(),
                'description' => 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.',
                'collections' => ['new-arrivals', 't-shirts'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
                'price' => 2499, 'weight_g' => 200, 'inventory' => 15, 'sku_prefix' => 'ACME-CTSH',
            ],
            [
                'title' => 'Premium Slim Fit Jeans', 'handle' => 'premium-slim-fit-jeans',
                'status' => 'active', 'vendor' => 'Acme Denim', 'product_type' => 'Pants',
                'tags' => ['new', 'sale'], 'published_at' => now(),
                'description' => 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.',
                'collections' => ['new-arrivals', 'pants-jeans', 'sale'],
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
                'price' => 7999, 'compare_at' => 9999, 'weight_g' => 800, 'inventory' => 8,
            ],
            [
                'title' => 'Organic Hoodie', 'handle' => 'organic-hoodie',
                'status' => 'active', 'vendor' => 'Acme Basics', 'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'], 'published_at' => now(),
                'description' => 'Made from 100% organic cotton. Warm, soft, and sustainably produced.',
                'collections' => ['new-arrivals'],
                'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]],
                'price' => 5999, 'weight_g' => 500, 'inventory' => 20,
            ],
            [
                'title' => 'Leather Belt', 'handle' => 'leather-belt',
                'status' => 'active', 'vendor' => 'Acme Accessories', 'product_type' => 'Accessories',
                'tags' => ['popular'], 'published_at' => now(),
                'description' => 'Genuine leather belt with brushed metal buckle. A wardrobe essential.',
                'collections' => [],
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
                'price' => 3499, 'weight_g' => 150, 'inventory' => 25,
            ],
            [
                'title' => 'Running Sneakers', 'handle' => 'running-sneakers',
                'status' => 'active', 'vendor' => 'Acme Sport', 'product_type' => 'Shoes',
                'tags' => ['trending'], 'published_at' => now(),
                'description' => 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.',
                'collections' => ['new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
                'price' => 11999, 'weight_g' => 600, 'inventory' => 5,
            ],
            [
                'title' => 'Graphic Print Tee', 'handle' => 'graphic-print-tee',
                'status' => 'active', 'vendor' => 'Acme Basics', 'product_type' => 'T-Shirts',
                'tags' => ['new'], 'published_at' => now(),
                'description' => 'Bold graphic print on soft cotton. Express yourself with this statement piece.',
                'collections' => ['t-shirts'],
                'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]],
                'price' => 2999, 'weight_g' => 210, 'inventory' => 18,
            ],
            [
                'title' => 'V-Neck Linen Tee', 'handle' => 'v-neck-linen-tee',
                'status' => 'active', 'vendor' => 'Acme Basics', 'product_type' => 'T-Shirts',
                'tags' => ['popular'], 'published_at' => now(),
                'description' => 'Lightweight linen blend v-neck. Perfect for warm summer days.',
                'collections' => ['t-shirts'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
                'price' => 3499, 'weight_g' => 180, 'inventory' => 12,
            ],
            [
                'title' => 'Striped Polo Shirt', 'handle' => 'striped-polo-shirt',
                'status' => 'active', 'vendor' => 'Acme Basics', 'product_type' => 'T-Shirts',
                'tags' => ['sale'], 'published_at' => now(),
                'description' => 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.',
                'collections' => ['t-shirts', 'sale'],
                'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]],
                'price' => 2799, 'compare_at' => 3999, 'weight_g' => 250, 'inventory' => 10,
            ],
            [
                'title' => 'Cargo Pants', 'handle' => 'cargo-pants',
                'status' => 'active', 'vendor' => 'Acme Workwear', 'product_type' => 'Pants',
                'tags' => ['popular'], 'published_at' => now(),
                'description' => 'Utility cargo pants with multiple pockets. Durable cotton twill construction.',
                'collections' => ['pants-jeans'],
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
                'price' => 5499, 'weight_g' => 700, 'inventory' => 14,
            ],
            [
                'title' => 'Chino Shorts', 'handle' => 'chino-shorts',
                'status' => 'active', 'vendor' => 'Acme Basics', 'product_type' => 'Pants',
                'tags' => ['new', 'trending'], 'published_at' => now(),
                'description' => 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.',
                'collections' => ['pants-jeans', 'new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
                'price' => 3999, 'weight_g' => 350, 'inventory' => 16,
            ],
            [
                'title' => 'Wide Leg Trousers', 'handle' => 'wide-leg-trousers',
                'status' => 'active', 'vendor' => 'Acme Denim', 'product_type' => 'Pants',
                'tags' => ['sale'], 'published_at' => now(),
                'description' => 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.',
                'collections' => ['pants-jeans', 'sale'],
                'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L']]],
                'price' => 4999, 'compare_at' => 6999, 'weight_g' => 550, 'inventory' => 7,
            ],
            [
                'title' => 'Wool Scarf', 'handle' => 'wool-scarf',
                'status' => 'active', 'vendor' => 'Acme Accessories', 'product_type' => 'Accessories',
                'tags' => ['popular'], 'published_at' => now(),
                'description' => 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.',
                'collections' => [],
                'options' => [['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']]],
                'price' => 2999, 'weight_g' => 120, 'inventory' => 30,
            ],
            [
                'title' => 'Canvas Tote Bag', 'handle' => 'canvas-tote-bag',
                'status' => 'active', 'vendor' => 'Acme Accessories', 'product_type' => 'Accessories',
                'tags' => ['trending'], 'published_at' => now(),
                'description' => 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.',
                'collections' => [],
                'options' => [['name' => 'Color', 'values' => ['Natural', 'Black']]],
                'price' => 1999, 'weight_g' => 300, 'inventory' => 40,
            ],
            [
                'title' => 'Bucket Hat', 'handle' => 'bucket-hat',
                'status' => 'active', 'vendor' => 'Acme Accessories', 'product_type' => 'Accessories',
                'tags' => ['new', 'trending'], 'published_at' => now(),
                'description' => 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.',
                'collections' => ['new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
                'price' => 2499, 'weight_g' => 80, 'inventory' => 22,
            ],
            [
                'title' => 'Unreleased Winter Jacket', 'handle' => 'unreleased-winter-jacket',
                'status' => 'draft', 'vendor' => 'Acme Outerwear', 'product_type' => 'Jackets',
                'tags' => ['limited'], 'published_at' => null,
                'description' => 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.',
                'collections' => [],
                'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]],
                'price' => 14999, 'weight_g' => 900, 'inventory' => 0,
            ],
            [
                'title' => 'Discontinued Raincoat', 'handle' => 'discontinued-raincoat',
                'status' => 'archived', 'vendor' => 'Acme Outerwear', 'product_type' => 'Jackets',
                'tags' => [], 'published_at' => now()->subMonths(6),
                'description' => 'Lightweight waterproof raincoat. This product has been discontinued.',
                'collections' => [],
                'options' => [['name' => 'Size', 'values' => ['M', 'L']]],
                'price' => 8999, 'weight_g' => 400, 'inventory' => 3,
            ],
            [
                'title' => 'Limited Edition Sneakers', 'handle' => 'limited-edition-sneakers',
                'status' => 'active', 'vendor' => 'Acme Sport', 'product_type' => 'Shoes',
                'tags' => ['limited'], 'published_at' => now(),
                'description' => 'Limited edition collaboration sneakers. Once they are gone, they are gone.',
                'collections' => [],
                'options' => [['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']]],
                'price' => 15999, 'weight_g' => 650, 'inventory' => 0,
            ],
            [
                'title' => 'Backorder Denim Jacket', 'handle' => 'backorder-denim-jacket',
                'status' => 'active', 'vendor' => 'Acme Denim', 'product_type' => 'Jackets',
                'tags' => ['popular'], 'published_at' => now(),
                'description' => 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.',
                'collections' => [],
                'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]],
                'price' => 9999, 'weight_g' => 750, 'inventory' => 0, 'inventory_policy' => 'continue',
            ],
            [
                'title' => 'Gift Card', 'handle' => 'gift-card',
                'status' => 'active', 'vendor' => 'Acme Fashion', 'product_type' => 'Gift Cards',
                'tags' => ['popular'], 'published_at' => now(),
                'description' => 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.',
                'collections' => [],
                'options' => [['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']]],
                'price' => 2500, 'weight_g' => 0, 'inventory' => 9999, 'requires_shipping' => false,
                'variant_prices' => [2500, 5000, 10000],
            ],
            [
                'title' => 'Cashmere Overcoat', 'handle' => 'cashmere-overcoat',
                'status' => 'active', 'vendor' => 'Acme Premium', 'product_type' => 'Jackets',
                'tags' => ['limited', 'new'], 'published_at' => now(),
                'description' => 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.',
                'collections' => ['new-arrivals'],
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Camel', 'Charcoal']],
                ],
                'price' => 49999, 'weight_g' => 1200, 'inventory' => 3,
            ],
        ];
    }
}
