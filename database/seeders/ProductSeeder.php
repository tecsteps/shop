<?php

namespace Database\Seeders;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
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
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $fashionCollections = Collection::query()
            ->where('store_id', $fashion->id)
            ->pluck('id', 'handle');

        $this->seedAcmeFashionProducts($fashion, $fashionCollections);
        $this->seedAcmeElectronicsProducts($electronics);
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $collections
     */
    private function seedAcmeFashionProducts(Store $store, \Illuminate\Support\Collection $collections): void
    {
        $products = $this->getFashionProductDefinitions();

        foreach ($products as $index => $def) {
            $product = Product::factory()->create([
                'store_id' => $store->id,
                'title' => $def['title'],
                'handle' => $def['handle'],
                'status' => $def['status'],
                'vendor' => $def['vendor'],
                'product_type' => $def['product_type'],
                'tags' => $def['tags'],
                'description_html' => '<p>'.$def['description'].'</p>',
                'published_at' => $def['published_at'],
            ]);

            $optionValueIds = $this->createOptionsAndVariants(
                $store,
                $product,
                $def['options'],
                $def['price'],
                $def['compare_at'] ?? null,
                $def['weight_g'],
                $def['inventory'],
                $def['inventory_policy'] ?? InventoryPolicy::Deny,
                $def['requires_shipping'] ?? true,
                $def['sku_prefix'] ?? null,
            );

            // Attach to collections
            foreach ($def['collections'] ?? [] as $position => $collectionHandle) {
                if ($collections->has($collectionHandle)) {
                    $product->collections()->attach($collections[$collectionHandle], ['position' => $position]);
                }
            }
        }
    }

    private function seedAcmeElectronicsProducts(Store $store): void
    {
        $electronicsCollections = Collection::query()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        $electronicsProducts = [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['featured', 'popular'],
                'description' => 'High-performance laptop with 15-inch display.',
                'options' => [['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']]],
                'prices' => [99999, 119999, 149999],
                'weight_g' => 1800,
                'inventory' => 10,
                'collections' => ['featured'],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['featured', 'new'],
                'description' => 'Premium wireless headphones with noise cancellation.',
                'options' => [['name' => 'Color', 'values' => ['Black', 'Silver']]],
                'prices' => [14999],
                'weight_g' => 250,
                'inventory' => 25,
                'collections' => ['featured'],
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => ['essential'],
                'description' => 'Durable USB-C cable, 2 meters long.',
                'options' => [],
                'prices' => [1299],
                'weight_g' => 50,
                'inventory' => 200,
                'collections' => ['accessories'],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['featured', 'popular'],
                'description' => 'Mechanical keyboard with customizable RGB lighting.',
                'options' => [['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']]],
                'prices' => [12999],
                'weight_g' => 1100,
                'inventory' => 15,
                'collections' => ['featured'],
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => ['essential'],
                'description' => 'Adjustable monitor stand with cable management.',
                'options' => [],
                'prices' => [4999],
                'weight_g' => 2500,
                'inventory' => 30,
                'collections' => ['accessories'],
            ],
        ];

        foreach ($electronicsProducts as $def) {
            $product = Product::factory()->create([
                'store_id' => $store->id,
                'title' => $def['title'],
                'handle' => $def['handle'],
                'status' => ProductStatus::Active,
                'vendor' => $def['vendor'],
                'product_type' => $def['product_type'],
                'tags' => $def['tags'],
                'description_html' => '<p>'.$def['description'].'</p>',
                'published_at' => now(),
            ]);

            if (empty($def['options'])) {
                // Single default variant
                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'sku' => 'ELEC-'.strtoupper(substr(str_replace('-', '', $def['handle']), 0, 8)),
                    'price_amount' => $def['prices'][0],
                    'weight_g' => $def['weight_g'],
                    'requires_shipping' => true,
                    'is_default' => true,
                    'position' => 0,
                ]);

                InventoryItem::factory()->withStock($def['inventory'])->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'policy' => InventoryPolicy::Deny,
                ]);
            } else {
                // Create options and variants
                $allOptionValues = [];
                foreach ($def['options'] as $optIdx => $opt) {
                    $option = ProductOption::factory()->create([
                        'product_id' => $product->id,
                        'name' => $opt['name'],
                        'position' => $optIdx,
                    ]);

                    $vals = [];
                    foreach ($opt['values'] as $valIdx => $val) {
                        $vals[] = ProductOptionValue::factory()->create([
                            'product_option_id' => $option->id,
                            'value' => $val,
                            'position' => $valIdx,
                        ]);
                    }
                    $allOptionValues[] = $vals;
                }

                $combos = $this->generateCombinations($allOptionValues);
                $position = 0;
                foreach ($combos as $combo) {
                    $price = count($def['prices']) > 1 ? $def['prices'][$position] ?? $def['prices'][0] : $def['prices'][0];
                    $labelParts = array_map(fn ($v) => $v->value, $combo);
                    $skuSuffix = strtoupper(implode('-', array_map(fn ($v) => substr(str_replace(' ', '', $v->value), 0, 4), $combo)));

                    $variant = ProductVariant::factory()->create([
                        'product_id' => $product->id,
                        'sku' => 'ELEC-'.strtoupper(substr($def['handle'], 0, 4)).'-'.$skuSuffix,
                        'price_amount' => $price,
                        'weight_g' => $def['weight_g'],
                        'requires_shipping' => true,
                        'is_default' => $position === 0,
                        'position' => $position,
                    ]);

                    $variant->optionValues()->attach(array_map(fn ($v) => $v->id, $combo));

                    InventoryItem::factory()->withStock($def['inventory'])->create([
                        'store_id' => $store->id,
                        'variant_id' => $variant->id,
                        'policy' => InventoryPolicy::Deny,
                    ]);

                    $position++;
                }
            }

            // Attach to collections
            foreach ($def['collections'] as $collectionHandle) {
                if ($electronicsCollections->has($collectionHandle)) {
                    $product->collections()->attach($electronicsCollections[$collectionHandle], ['position' => 0]);
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFashionProductDefinitions(): array
    {
        return [
            // Product 1
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'description' => 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                    ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']],
                ],
                'price' => 2499,
                'weight_g' => 200,
                'inventory' => 15,
                'sku_prefix' => 'ACME-CTSH',
                'collections' => ['new-arrivals', 't-shirts'],
            ],
            // Product 2
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description' => 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['28', '30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Blue', 'Black']],
                ],
                'price' => 7999,
                'compare_at' => 9999,
                'weight_g' => 800,
                'inventory' => 8,
                'sku_prefix' => 'ACME-JEANS',
                'collections' => ['new-arrivals', 'pants-jeans', 'sale'],
            ],
            // Product 3
            [
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description' => 'Made from 100% organic cotton. Warm, soft, and sustainably produced.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 5999,
                'weight_g' => 500,
                'inventory' => 20,
                'sku_prefix' => 'ACME-HOOD',
                'collections' => ['new-arrivals'],
            ],
            // Product 4
            [
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Genuine leather belt with brushed metal buckle. A wardrobe essential.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Brown', 'Black']],
                ],
                'price' => 3499,
                'weight_g' => 150,
                'inventory' => 25,
                'sku_prefix' => 'ACME-BELT',
                'collections' => [],
            ],
            // Product 5
            [
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description' => 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']],
                    ['name' => 'Color', 'values' => ['White', 'Black']],
                ],
                'price' => 11999,
                'weight_g' => 600,
                'inventory' => 5,
                'sku_prefix' => 'ACME-SNKR',
                'collections' => ['new-arrivals'],
            ],
            // Product 6
            [
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description' => 'Bold graphic print on soft cotton. Express yourself with this statement piece.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 2999,
                'weight_g' => 210,
                'inventory' => 18,
                'sku_prefix' => 'ACME-GRPH',
                'collections' => ['t-shirts'],
            ],
            // Product 7
            [
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description' => 'Lightweight linen blend v-neck. Perfect for warm summer days.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Beige', 'Olive', 'Sky Blue']],
                ],
                'price' => 3499,
                'weight_g' => 180,
                'inventory' => 12,
                'sku_prefix' => 'ACME-VNCK',
                'collections' => ['t-shirts'],
            ],
            // Product 8
            [
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description' => 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 2799,
                'compare_at' => 3999,
                'weight_g' => 250,
                'inventory' => 10,
                'sku_prefix' => 'ACME-POLO',
                'collections' => ['t-shirts', 'sale'],
            ],
            // Product 9
            [
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description' => 'Utility cargo pants with multiple pockets. Durable cotton twill construction.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Khaki', 'Olive', 'Black']],
                ],
                'price' => 5499,
                'weight_g' => 700,
                'inventory' => 14,
                'sku_prefix' => 'ACME-CRGO',
                'collections' => ['pants-jeans'],
            ],
            // Product 10
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description' => 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['30', '32', '34', '36']],
                    ['name' => 'Color', 'values' => ['Navy', 'Sand']],
                ],
                'price' => 3999,
                'weight_g' => 350,
                'inventory' => 16,
                'sku_prefix' => 'ACME-CHNO',
                'collections' => ['pants-jeans', 'new-arrivals'],
            ],
            // Product 11
            [
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description' => 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                ],
                'price' => 4999,
                'compare_at' => 6999,
                'weight_g' => 550,
                'inventory' => 7,
                'sku_prefix' => 'ACME-WLEG',
                'collections' => ['pants-jeans', 'sale'],
            ],
            // Product 12
            [
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Color', 'values' => ['Grey', 'Burgundy', 'Navy']],
                ],
                'price' => 2999,
                'weight_g' => 120,
                'inventory' => 30,
                'sku_prefix' => 'ACME-SCRF',
                'collections' => [],
            ],
            // Product 13
            [
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description' => 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Color', 'values' => ['Natural', 'Black']],
                ],
                'price' => 1999,
                'weight_g' => 300,
                'inventory' => 40,
                'sku_prefix' => 'ACME-TOTE',
                'collections' => [],
            ],
            // Product 14
            [
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description' => 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S/M', 'L/XL']],
                    ['name' => 'Color', 'values' => ['Beige', 'Black', 'Olive']],
                ],
                'price' => 2499,
                'weight_g' => 80,
                'inventory' => 22,
                'sku_prefix' => 'ACME-BHAT',
                'collections' => ['new-arrivals'],
            ],
            // Product 15 - DRAFT
            [
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'status' => ProductStatus::Draft,
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'description' => 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.',
                'published_at' => null,
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 14999,
                'weight_g' => 900,
                'inventory' => 0,
                'sku_prefix' => 'ACME-WJKT',
                'collections' => [],
            ],
            // Product 16 - ARCHIVED
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => ProductStatus::Archived,
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description' => 'Lightweight waterproof raincoat. This product has been discontinued.',
                'published_at' => now()->subMonths(6),
                'options' => [
                    ['name' => 'Size', 'values' => ['M', 'L']],
                ],
                'price' => 8999,
                'weight_g' => 400,
                'inventory' => 3,
                'sku_prefix' => 'ACME-RAIN',
                'collections' => [],
            ],
            // Product 17 - SOLD OUT (deny policy)
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description' => 'Limited edition collaboration sneakers. Once they are gone, they are gone.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['EU 40', 'EU 42', 'EU 44']],
                ],
                'price' => 15999,
                'weight_g' => 650,
                'inventory' => 0,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-LSNK',
                'collections' => [],
            ],
            // Product 18 - BACKORDER (continue policy)
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description' => 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']],
                ],
                'price' => 9999,
                'weight_g' => 750,
                'inventory' => 0,
                'inventory_policy' => InventoryPolicy::Continue,
                'sku_prefix' => 'ACME-DJKT',
                'collections' => [],
            ],
            // Product 19 - DIGITAL (gift card)
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description' => 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Amount', 'values' => ['25 EUR', '50 EUR', '100 EUR']],
                ],
                'price' => 2500, // will be overridden per variant
                'weight_g' => 0,
                'inventory' => 9999,
                'requires_shipping' => false,
                'sku_prefix' => 'ACME-GIFT',
                'collections' => [],
                'custom_prices' => [2500, 5000, 10000],
                'custom_skus' => ['ACME-GIFT-25', 'ACME-GIFT-50', 'ACME-GIFT-100'],
            ],
            // Product 20 - EXPENSIVE
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description' => 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.',
                'published_at' => now(),
                'options' => [
                    ['name' => 'Size', 'values' => ['S', 'M', 'L']],
                    ['name' => 'Color', 'values' => ['Camel', 'Charcoal']],
                ],
                'price' => 49999,
                'weight_g' => 1200,
                'inventory' => 3,
                'sku_prefix' => 'ACME-CASH',
                'collections' => ['new-arrivals'],
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, values: list<string>}>  $options
     */
    private function createOptionsAndVariants(
        Store $store,
        Product $product,
        array $options,
        int $price,
        ?int $compareAt,
        int $weightG,
        int $inventory,
        InventoryPolicy $inventoryPolicy,
        bool $requiresShipping = true,
        ?string $skuPrefix = null,
    ): void {
        if (empty($options)) {
            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => ($skuPrefix ?? strtoupper(substr($product->handle, 0, 8))).'-DEF',
                'price_amount' => $price,
                'compare_at_amount' => $compareAt,
                'weight_g' => $weightG,
                'requires_shipping' => $requiresShipping,
                'is_default' => true,
                'position' => 0,
            ]);

            InventoryItem::factory()->withStock($inventory)->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'policy' => $inventoryPolicy,
            ]);

            return;
        }

        $allOptionValues = [];
        foreach ($options as $optIdx => $opt) {
            $option = ProductOption::factory()->create([
                'product_id' => $product->id,
                'name' => $opt['name'],
                'position' => $optIdx,
            ]);

            $vals = [];
            foreach ($opt['values'] as $valIdx => $val) {
                $vals[] = ProductOptionValue::factory()->create([
                    'product_option_id' => $option->id,
                    'value' => $val,
                    'position' => $valIdx,
                ]);
            }
            $allOptionValues[] = $vals;
        }

        $combos = $this->generateCombinations($allOptionValues);
        $position = 0;

        // Check for custom prices/skus (gift card)
        $customPrices = $product->handle === 'gift-card' ? [2500, 5000, 10000] : null;
        $customSkus = $product->handle === 'gift-card' ? ['ACME-GIFT-25', 'ACME-GIFT-50', 'ACME-GIFT-100'] : null;

        foreach ($combos as $combo) {
            $variantPrice = $customPrices[$position] ?? $price;
            $variantSku = $customSkus[$position] ?? $this->generateSku($skuPrefix ?? 'SKU', $combo);

            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => $variantSku,
                'price_amount' => $variantPrice,
                'compare_at_amount' => $compareAt,
                'weight_g' => $weightG,
                'requires_shipping' => $requiresShipping,
                'is_default' => $position === 0,
                'position' => $position,
            ]);

            $variant->optionValues()->attach(array_map(fn ($v) => $v->id, $combo));

            InventoryItem::factory()->withStock($inventory)->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'policy' => $inventoryPolicy,
            ]);

            $position++;
        }
    }

    /**
     * @param  array<int, list<ProductOptionValue>>  $optionValueGroups
     * @return list<list<ProductOptionValue>>
     */
    private function generateCombinations(array $optionValueGroups): array
    {
        if (empty($optionValueGroups)) {
            return [[]];
        }

        $first = array_shift($optionValueGroups);
        $rest = $this->generateCombinations($optionValueGroups);
        $result = [];

        foreach ($first as $value) {
            foreach ($rest as $combo) {
                $result[] = array_merge([$value], $combo);
            }
        }

        return $result;
    }

    /**
     * @param  list<ProductOptionValue>  $combo
     */
    private function generateSku(string $prefix, array $combo): string
    {
        $parts = array_map(function ($v) {
            $val = $v->value;
            $val = str_replace(['/', ' '], ['', ''], $val);

            return strtoupper(substr($val, 0, 3));
        }, $combo);

        return $prefix.'-'.implode('-', $parts);
    }
}
