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
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        app()->instance('current_store', $fashion);

        $this->seedAcmeFashion($fashion);
        $this->seedAcmeElectronics($electronics);
    }

    private function seedAcmeFashion(Store $store): void
    {
        // Fetch collections for assignments
        $newArrivals = Collection::where('store_id', $store->id)->where('handle', 'new-arrivals')->first();
        $tShirts = Collection::where('store_id', $store->id)->where('handle', 't-shirts')->first();
        $pantsJeans = Collection::where('store_id', $store->id)->where('handle', 'pants-jeans')->first();
        $sale = Collection::where('store_id', $store->id)->where('handle', 'sale')->first();

        // Product definitions: [handle, title, vendor, type, tags, desc, status, published_at, price, compare_at, options, collections, inventory_qty, inventory_policy]
        $products = $this->getFashionProducts();

        foreach ($products as $i => $p) {
            $product = Product::factory()->create([
                'store_id' => $store->id,
                'title' => $p['title'],
                'handle' => $p['handle'],
                'status' => $p['status'],
                'vendor' => $p['vendor'],
                'product_type' => $p['product_type'],
                'tags' => $p['tags'],
                'description_html' => '<p>'.$p['description'].'</p>',
                'published_at' => $p['published_at'],
            ]);

            // Create options and values
            $optionValues = [];
            foreach ($p['options'] as $optIdx => $option) {
                $optionModel = ProductOption::factory()->create([
                    'product_id' => $product->id,
                    'name' => $option['name'],
                    'position' => $optIdx,
                ]);

                $values = [];
                foreach ($option['values'] as $valIdx => $val) {
                    $values[] = ProductOptionValue::factory()->create([
                        'product_option_id' => $optionModel->id,
                        'value' => $val,
                        'position' => $valIdx,
                    ]);
                }
                $optionValues[] = $values;
            }

            // Generate variants from option combinations
            $combinations = $this->generateCombinations($optionValues);
            $position = 0;

            foreach ($combinations as $combo) {
                $comboValues = is_array($combo) ? $combo : [$combo];
                $skuParts = [$p['sku_prefix'] ?? 'SKU'];
                foreach ($comboValues as $v) {
                    $skuParts[] = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $v->value), 0, 3));
                }

                $variantPrice = isset($p['custom_variant_prices'])
                    ? ($p['custom_variant_prices'][$position] ?? $p['price'])
                    : $p['price'];

                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'sku' => implode('-', $skuParts).'-'.str_pad((string) $position, 2, '0', STR_PAD_LEFT),
                    'price_amount' => $variantPrice,
                    'compare_at_amount' => $p['compare_at'] ?? null,
                    'currency' => 'EUR',
                    'weight_g' => $p['weight_g'],
                    'requires_shipping' => $p['requires_shipping'] ?? true,
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ]);

                // Attach option values to variant
                foreach ($comboValues as $v) {
                    $variant->optionValues()->attach($v->id);
                }

                InventoryItem::factory()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => $p['inventory_qty'],
                    'quantity_reserved' => 0,
                    'policy' => $p['inventory_policy'],
                ]);

                $position++;
            }

            // Assign to collections
            $collectionMap = [
                'new-arrivals' => $newArrivals,
                't-shirts' => $tShirts,
                'pants-jeans' => $pantsJeans,
                'sale' => $sale,
            ];

            foreach ($p['collections'] as $colIdx => $colHandle) {
                $col = $collectionMap[$colHandle] ?? null;
                if ($col) {
                    $col->products()->attach($product->id, ['position' => $colIdx]);
                }
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getFashionProducts(): array
    {
        return [
            // Product 1
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'description' => 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 2499,
                'compare_at' => null,
                'weight_g' => 200,
                'inventory_qty' => 15,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-CTSH',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
                'collections' => ['new-arrivals', 't-shirts'],
            ],
            // Product 2
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description' => 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 7999,
                'compare_at' => 9999,
                'weight_g' => 800,
                'inventory_qty' => 8,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-JEANS',
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
                'collections' => ['new-arrivals', 'pants-jeans', 'sale'],
            ],
            // Product 3
            [
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description' => 'Made from 100% organic cotton. Warm, soft, and sustainably produced.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 5999,
                'compare_at' => null,
                'weight_g' => 500,
                'inventory_qty' => 20,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-HOOD',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'collections' => ['new-arrivals'],
            ],
            // Product 4
            [
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Genuine leather belt with brushed metal buckle. A wardrobe essential.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 3499,
                'compare_at' => null,
                'weight_g' => 150,
                'inventory_qty' => 25,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-BELT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
                'collections' => [],
            ],
            // Product 5
            [
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description' => 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 11999,
                'compare_at' => null,
                'weight_g' => 600,
                'inventory_qty' => 5,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-RUN',
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
                'collections' => ['new-arrivals'],
            ],
            // Product 6
            [
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description' => 'Bold graphic print on soft cotton. Express yourself with this statement piece.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 2999,
                'compare_at' => null,
                'weight_g' => 210,
                'inventory_qty' => 18,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-GPT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'collections' => ['t-shirts'],
            ],
            // Product 7
            [
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description' => 'Lightweight linen blend v-neck. Perfect for warm summer days.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 3499,
                'compare_at' => null,
                'weight_g' => 180,
                'inventory_qty' => 12,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-VNT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
                'collections' => ['t-shirts'],
            ],
            // Product 8
            [
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description' => 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 2799,
                'compare_at' => 3999,
                'weight_g' => 250,
                'inventory_qty' => 10,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-POLO',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'collections' => ['t-shirts', 'sale'],
            ],
            // Product 9
            [
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description' => 'Utility cargo pants with multiple pockets. Durable cotton twill construction.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 5499,
                'compare_at' => null,
                'weight_g' => 700,
                'inventory_qty' => 14,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-CARGO',
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
                'collections' => ['pants-jeans'],
            ],
            // Product 10
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description' => 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 3999,
                'compare_at' => null,
                'weight_g' => 350,
                'inventory_qty' => 16,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-CHIN',
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
                'collections' => ['pants-jeans', 'new-arrivals'],
            ],
            // Product 11
            [
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description' => 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 4999,
                'compare_at' => 6999,
                'weight_g' => 550,
                'inventory_qty' => 7,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-WLT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ],
                'collections' => ['pants-jeans', 'sale'],
            ],
            // Product 12
            [
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 2999,
                'compare_at' => null,
                'weight_g' => 120,
                'inventory_qty' => 30,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-SCRF',
                'options' => [
                    ['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']],
                ],
                'collections' => [],
            ],
            // Product 13
            [
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description' => 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 1999,
                'compare_at' => null,
                'weight_g' => 300,
                'inventory_qty' => 40,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-TOTE',
                'options' => [
                    ['name' => 'Color', 'values' => ['Natural', 'Black']],
                ],
                'collections' => [],
            ],
            // Product 14
            [
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description' => 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 2499,
                'compare_at' => null,
                'weight_g' => 80,
                'inventory_qty' => 22,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-BHAT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
                'collections' => ['new-arrivals'],
            ],
            // Product 15 - DRAFT
            [
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'description' => 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.',
                'status' => 'draft',
                'published_at' => null,
                'price' => 14999,
                'compare_at' => null,
                'weight_g' => 900,
                'inventory_qty' => 0,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-WJKT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'collections' => [],
            ],
            // Product 16 - ARCHIVED
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description' => 'Lightweight waterproof raincoat. This product has been discontinued.',
                'status' => 'archived',
                'published_at' => now()->subMonths(6)->toIso8601String(),
                'price' => 8999,
                'compare_at' => null,
                'weight_g' => 400,
                'inventory_qty' => 3,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-RAIN',
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
                'collections' => [],
            ],
            // Product 17 - SOLD OUT (deny)
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description' => 'Limited edition collaboration sneakers. Once they are gone, they are gone.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 15999,
                'compare_at' => null,
                'weight_g' => 650,
                'inventory_qty' => 0,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-LSNK',
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']],
                ],
                'collections' => [],
            ],
            // Product 18 - BACKORDER (continue)
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description' => 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 9999,
                'compare_at' => null,
                'weight_g' => 750,
                'inventory_qty' => 0,
                'inventory_policy' => 'continue',
                'sku_prefix' => 'ACME-DJKT',
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'collections' => [],
            ],
            // Product 19 - DIGITAL (gift card)
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description' => 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 2500, // will be overridden per variant
                'compare_at' => null,
                'weight_g' => 0,
                'requires_shipping' => false,
                'inventory_qty' => 9999,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-GIFT',
                'options' => [
                    ['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']],
                ],
                'collections' => [],
                'custom_variant_prices' => [2500, 5000, 10000],
            ],
            // Product 20
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description' => 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.',
                'status' => 'active',
                'published_at' => now()->toIso8601String(),
                'price' => 49999,
                'compare_at' => null,
                'weight_g' => 1200,
                'inventory_qty' => 3,
                'inventory_policy' => 'deny',
                'sku_prefix' => 'ACME-COAT',
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
        app()->instance('current_store', $store);

        $featured = Collection::where('store_id', $store->id)->where('handle', 'featured')->first();
        $accessories = Collection::where('store_id', $store->id)->where('handle', 'accessories')->first();

        $products = [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['popular'],
                'description' => 'High-performance laptop for professionals.',
                'options' => [['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']]],
                'custom_variant_prices' => [99999, 119999, 149999],
                'weight_g' => 1800,
                'inventory_qty' => 10,
                'collections' => [$featured],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['trending'],
                'description' => 'Premium wireless headphones with noise cancellation.',
                'options' => [['name' => 'Color', 'values' => ['Black', 'Silver']]],
                'custom_variant_prices' => [14999, 14999],
                'weight_g' => 250,
                'inventory_qty' => 25,
                'collections' => [$featured],
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => [],
                'description' => 'Durable USB-C cable, 2 meters.',
                'options' => [],
                'custom_variant_prices' => [1299],
                'weight_g' => 50,
                'inventory_qty' => 200,
                'collections' => [$accessories],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['popular'],
                'description' => 'Premium mechanical keyboard with hot-swap switches.',
                'options' => [['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']]],
                'custom_variant_prices' => [12999, 12999, 12999],
                'weight_g' => 1100,
                'inventory_qty' => 15,
                'collections' => [$featured],
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => [],
                'description' => 'Ergonomic monitor stand with cable management.',
                'options' => [],
                'custom_variant_prices' => [4999],
                'weight_g' => 2500,
                'inventory_qty' => 30,
                'collections' => [$accessories],
            ],
        ];

        foreach ($products as $p) {
            $product = Product::factory()->create([
                'store_id' => $store->id,
                'title' => $p['title'],
                'handle' => $p['handle'],
                'status' => 'active',
                'vendor' => $p['vendor'],
                'product_type' => $p['product_type'],
                'tags' => $p['tags'],
                'description_html' => '<p>'.$p['description'].'</p>',
                'published_at' => now()->toIso8601String(),
            ]);

            if (empty($p['options'])) {
                // Single default variant
                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(str_replace(' ', '-', $p['title'])).'-DEFAULT',
                    'price_amount' => $p['custom_variant_prices'][0],
                    'currency' => 'EUR',
                    'weight_g' => $p['weight_g'],
                    'is_default' => true,
                    'position' => 0,
                    'status' => 'active',
                ]);

                InventoryItem::factory()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => $p['inventory_qty'],
                    'policy' => 'deny',
                ]);
            } else {
                $optionValues = [];
                foreach ($p['options'] as $optIdx => $option) {
                    $optionModel = ProductOption::factory()->create([
                        'product_id' => $product->id,
                        'name' => $option['name'],
                        'position' => $optIdx,
                    ]);

                    $values = [];
                    foreach ($option['values'] as $valIdx => $val) {
                        $values[] = ProductOptionValue::factory()->create([
                            'product_option_id' => $optionModel->id,
                            'value' => $val,
                            'position' => $valIdx,
                        ]);
                    }
                    $optionValues[] = $values;
                }

                $combinations = $this->generateCombinations($optionValues);
                $priceIdx = 0;

                foreach ($combinations as $combo) {
                    $comboValues = is_array($combo) ? $combo : [$combo];

                    $variant = ProductVariant::factory()->create([
                        'product_id' => $product->id,
                        'sku' => strtoupper(str_replace(' ', '-', $p['title'])).'-'.str_pad((string) $priceIdx, 2, '0', STR_PAD_LEFT),
                        'price_amount' => $p['custom_variant_prices'][$priceIdx] ?? $p['custom_variant_prices'][0],
                        'currency' => 'EUR',
                        'weight_g' => $p['weight_g'],
                        'is_default' => $priceIdx === 0,
                        'position' => $priceIdx,
                        'status' => 'active',
                    ]);

                    foreach ($comboValues as $v) {
                        $variant->optionValues()->attach($v->id);
                    }

                    InventoryItem::factory()->create([
                        'store_id' => $store->id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => $p['inventory_qty'],
                        'policy' => 'deny',
                    ]);

                    $priceIdx++;
                }
            }

            foreach ($p['collections'] as $colIdx => $col) {
                if ($col) {
                    $col->products()->attach($product->id, ['position' => $colIdx]);
                }
            }
        }
    }

    /**
     * Generate all combinations of option values (cartesian product).
     *
     * @param  list<list<ProductOptionValue>>  $optionValues
     * @return list<list<ProductOptionValue>|ProductOptionValue>
     */
    private function generateCombinations(array $optionValues): array
    {
        if (empty($optionValues)) {
            return [];
        }

        if (count($optionValues) === 1) {
            return $optionValues[0];
        }

        $result = [[]];
        foreach ($optionValues as $values) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($values as $value) {
                    $combo = is_array($existing) ? $existing : [$existing];
                    $combo[] = $value;
                    $newResult[] = $combo;
                }
            }
            $result = $newResult;
        }

        return $result;
    }
}
