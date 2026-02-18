<?php

namespace Database\Seeders;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
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
        $this->seedAcmeFashion();
        $this->seedAcmeElectronics();
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::where('handle', 'acme-fashion')->first();
        if (! $store) {
            return;
        }

        // Collections
        $newArrivals = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'New Arrivals',
            'handle' => 'new-arrivals',
            'description_html' => '<p>Discover the latest additions to our store.</p>',
        ]);

        $tShirts = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'T-Shirts',
            'handle' => 't-shirts',
            'description_html' => '<p>Premium cotton tees for every occasion.</p>',
        ]);

        $pantsJeans = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Pants & Jeans',
            'handle' => 'pants-jeans',
            'description_html' => '<p>Find the perfect fit from our denim and trouser range.</p>',
        ]);

        $sale = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Sale',
            'handle' => 'sale',
            'description_html' => '<p>Great deals on selected items.</p>',
        ]);

        /**
         * @var array<string, array{
         *     title: string,
         *     handle: string,
         *     status: ProductStatus,
         *     vendor: string,
         *     product_type: string,
         *     tags: list<string>,
         *     description_html: string,
         *     published_at: \Illuminate\Support\Carbon|null,
         *     collections: list<Collection>,
         *     options: array<string, list<string>>,
         *     price: int,
         *     compare_at: int|null,
         *     weight: int,
         *     requires_shipping: bool,
         *     inventory: int,
         *     inventory_policy: InventoryPolicy,
         *     sku_prefix: string,
         * }>
         */
        $products = [
            [
                'title' => 'Classic Cotton T-Shirt',
                'handle' => 'classic-cotton-t-shirt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new', 'popular'],
                'description_html' => '<p>A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.</p>',
                'published_at' => now(),
                'collections' => [$newArrivals, $tShirts],
                'options' => ['Size' => ['S', 'M', 'L', 'XL'], 'Color' => ['White', 'Black', 'Navy']],
                'price' => 2499,
                'compare_at' => null,
                'weight' => 200,
                'requires_shipping' => true,
                'inventory' => 15,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-CTSH',
            ],
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description_html' => '<p>Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.</p>',
                'published_at' => now(),
                'collections' => [$newArrivals, $pantsJeans, $sale],
                'options' => ['Size' => ['28', '30', '32', '34', '36'], 'Color' => ['Blue', 'Black']],
                'price' => 7999,
                'compare_at' => 9999,
                'weight' => 800,
                'requires_shipping' => true,
                'inventory' => 8,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-JEANS',
            ],
            [
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Made from 100% organic cotton. Warm, soft, and sustainably produced.</p>',
                'published_at' => now(),
                'collections' => [$newArrivals],
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 5999,
                'compare_at' => null,
                'weight' => 500,
                'requires_shipping' => true,
                'inventory' => 20,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-HOOD',
            ],
            [
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description_html' => '<p>Genuine leather belt with brushed metal buckle. A wardrobe essential.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => ['Size' => ['S/M', 'L/XL'], 'Color' => ['Brown', 'Black']],
                'price' => 3499,
                'compare_at' => null,
                'weight' => 150,
                'requires_shipping' => true,
                'inventory' => 25,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-BELT',
            ],
            [
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description_html' => '<p>Lightweight running sneakers with responsive cushioning and breathable mesh upper.</p>',
                'published_at' => now(),
                'collections' => [$newArrivals],
                'options' => ['Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'], 'Color' => ['White', 'Black']],
                'price' => 11999,
                'compare_at' => null,
                'weight' => 600,
                'requires_shipping' => true,
                'inventory' => 5,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-RUN',
            ],
            [
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description_html' => '<p>Bold graphic print on soft cotton. Express yourself with this statement piece.</p>',
                'published_at' => now(),
                'collections' => [$tShirts],
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 2999,
                'compare_at' => null,
                'weight' => 210,
                'requires_shipping' => true,
                'inventory' => 18,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-GPT',
            ],
            [
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description_html' => '<p>Lightweight linen blend v-neck. Perfect for warm summer days.</p>',
                'published_at' => now(),
                'collections' => [$tShirts],
                'options' => ['Size' => ['S', 'M', 'L'], 'Color' => ['Beige', 'Olive', 'Sky Blue']],
                'price' => 3499,
                'compare_at' => null,
                'weight' => 180,
                'requires_shipping' => true,
                'inventory' => 12,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-VNK',
            ],
            [
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description_html' => '<p>Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.</p>',
                'published_at' => now(),
                'collections' => [$tShirts, $sale],
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 2799,
                'compare_at' => 3999,
                'weight' => 250,
                'requires_shipping' => true,
                'inventory' => 10,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-POLO',
            ],
            [
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description_html' => '<p>Utility cargo pants with multiple pockets. Durable cotton twill construction.</p>',
                'published_at' => now(),
                'collections' => [$pantsJeans],
                'options' => ['Size' => ['30', '32', '34', '36'], 'Color' => ['Khaki', 'Olive', 'Black']],
                'price' => 5499,
                'compare_at' => null,
                'weight' => 700,
                'requires_shipping' => true,
                'inventory' => 14,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-CARGO',
            ],
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.</p>',
                'published_at' => now(),
                'collections' => [$pantsJeans, $newArrivals],
                'options' => ['Size' => ['30', '32', '34', '36'], 'Color' => ['Navy', 'Sand']],
                'price' => 3999,
                'compare_at' => null,
                'weight' => 350,
                'requires_shipping' => true,
                'inventory' => 16,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-CHINO',
            ],
            [
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description_html' => '<p>Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.</p>',
                'published_at' => now(),
                'collections' => [$pantsJeans, $sale],
                'options' => ['Size' => ['S', 'M', 'L']],
                'price' => 4999,
                'compare_at' => 6999,
                'weight' => 550,
                'requires_shipping' => true,
                'inventory' => 7,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-WLT',
            ],
            [
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description_html' => '<p>Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => ['Color' => ['Grey', 'Burgundy', 'Navy']],
                'price' => 2999,
                'compare_at' => null,
                'weight' => 120,
                'requires_shipping' => true,
                'inventory' => 30,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-SCRF',
            ],
            [
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description_html' => '<p>Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => ['Color' => ['Natural', 'Black']],
                'price' => 1999,
                'compare_at' => null,
                'weight' => 300,
                'requires_shipping' => true,
                'inventory' => 40,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-TOTE',
            ],
            [
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description_html' => '<p>Lightweight bucket hat for sun protection. Packable design, washed cotton twill.</p>',
                'published_at' => now(),
                'collections' => [$newArrivals],
                'options' => ['Size' => ['S/M', 'L/XL'], 'Color' => ['Beige', 'Black', 'Olive']],
                'price' => 2499,
                'compare_at' => null,
                'weight' => 80,
                'requires_shipping' => true,
                'inventory' => 22,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-BHAT',
            ],
            [
                'title' => 'Unreleased Winter Jacket',
                'handle' => 'unreleased-winter-jacket',
                'status' => ProductStatus::Draft,
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => ['limited'],
                'description_html' => '<p>Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.</p>',
                'published_at' => null,
                'collections' => [],
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 14999,
                'compare_at' => null,
                'weight' => 900,
                'requires_shipping' => true,
                'inventory' => 0,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-WJKT',
            ],
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => ProductStatus::Archived,
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description_html' => '<p>Lightweight waterproof raincoat. This product has been discontinued.</p>',
                'published_at' => now()->subMonths(6),
                'collections' => [],
                'options' => ['Size' => ['M', 'L']],
                'price' => 8999,
                'compare_at' => null,
                'weight' => 400,
                'requires_shipping' => true,
                'inventory' => 3,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-RAIN',
            ],
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description_html' => '<p>Limited edition collaboration sneakers. Once they are gone, they are gone.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => ['Size' => ['EU 40', 'EU 42', 'EU 44']],
                'price' => 15999,
                'compare_at' => null,
                'weight' => 650,
                'requires_shipping' => true,
                'inventory' => 0,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-LSNK',
            ],
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description_html' => '<p>Classic denim jacket. Currently on backorder - ships within 2-3 weeks.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 9999,
                'compare_at' => null,
                'weight' => 750,
                'requires_shipping' => true,
                'inventory' => 0,
                'inventory_policy' => InventoryPolicy::Continue,
                'sku_prefix' => 'ACME-DJKT',
            ],
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description_html' => '<p>Digital gift card delivered via email. The perfect gift when you are not sure what to choose.</p>',
                'published_at' => now(),
                'collections' => [],
                'options' => ['Amount' => ['25 EUR', '50 EUR', '100 EUR']],
                'price' => 0,
                'compare_at' => null,
                'weight' => 0,
                'requires_shipping' => false,
                'inventory' => 9999,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-GIFT',
                'gift_card_prices' => [2500, 5000, 10000],
            ],
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'status' => ProductStatus::Active,
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description_html' => '<p>Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.</p>',
                'published_at' => now(),
                'collections' => [$newArrivals],
                'options' => ['Size' => ['S', 'M', 'L'], 'Color' => ['Camel', 'Charcoal']],
                'price' => 49999,
                'compare_at' => null,
                'weight' => 1200,
                'requires_shipping' => true,
                'inventory' => 3,
                'inventory_policy' => InventoryPolicy::Deny,
                'sku_prefix' => 'ACME-CASH',
            ],
        ];

        foreach ($products as $productData) {
            $this->createProduct($store, $productData);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createProduct(Store $store, array $data): void
    {
        $product = Product::factory()->create([
            'store_id' => $store->id,
            'title' => $data['title'],
            'handle' => $data['handle'],
            'status' => $data['status'],
            'vendor' => $data['vendor'],
            'product_type' => $data['product_type'],
            'tags' => $data['tags'],
            'description_html' => $data['description_html'],
            'published_at' => $data['published_at'],
        ]);

        // Create options and their values
        $optionValues = [];
        $optionPosition = 0;
        foreach ($data['options'] as $optionName => $values) {
            $option = ProductOption::factory()->create([
                'product_id' => $product->id,
                'name' => $optionName,
                'position' => $optionPosition++,
            ]);

            $optionValues[$optionName] = [];
            foreach ($values as $valuePosition => $value) {
                $optionValues[$optionName][] = ProductOptionValue::factory()->create([
                    'product_option_id' => $option->id,
                    'value' => $value,
                    'position' => $valuePosition,
                ]);
            }
        }

        // Generate all variant combinations
        $optionNames = array_keys($data['options']);
        $combinations = $this->generateCombinations($optionValues);

        $position = 0;
        $isFirst = true;

        $giftCardPrices = $data['gift_card_prices'] ?? null;

        foreach ($combinations as $combo) {
            $skuParts = [$data['sku_prefix']];
            $valueIds = [];
            foreach ($combo as $optionValueModel) {
                $skuParts[] = strtoupper(substr(str_replace(['/', ' '], '', $optionValueModel->value), 0, 3));
                $valueIds[] = $optionValueModel->id;
            }

            $price = $data['price'];
            if ($giftCardPrices !== null) {
                $price = $giftCardPrices[$position] ?? $data['price'];
            }

            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => implode('-', $skuParts),
                'price_amount' => $price,
                'compare_at_amount' => $data['compare_at'],
                'currency' => 'EUR',
                'weight_g' => $data['weight'],
                'requires_shipping' => $data['requires_shipping'],
                'is_default' => $isFirst,
                'position' => $position,
                'status' => VariantStatus::Active,
            ]);

            $variant->optionValues()->attach($valueIds);

            InventoryItem::factory()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $data['inventory'],
                'quantity_reserved' => 0,
                'policy' => $data['inventory_policy'],
            ]);

            $position++;
            $isFirst = false;
        }

        // Attach to collections
        foreach ($data['collections'] as $collectionPosition => $collection) {
            $collection->products()->attach($product->id, ['position' => $collectionPosition]);
        }
    }

    /**
     * @param  array<string, list<ProductOptionValue>>  $optionValues
     * @return list<list<ProductOptionValue>>
     */
    private function generateCombinations(array $optionValues): array
    {
        $keys = array_keys($optionValues);
        if (empty($keys)) {
            return [[]];
        }

        $result = [[]];
        foreach ($keys as $key) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($optionValues[$key] as $value) {
                    $newResult[] = [...$existing, $value];
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::where('handle', 'acme-electronics')->first();
        if (! $store) {
            return;
        }

        $featured = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Featured',
            'handle' => 'featured',
        ]);

        $accessories = Collection::factory()->create([
            'store_id' => $store->id,
            'title' => 'Accessories',
            'handle' => 'accessories',
        ]);

        $electronicsProducts = [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'options' => ['Storage' => ['256GB', '512GB', '1TB']],
                'prices' => [99999, 119999, 149999],
                'weight' => 1800,
                'inventory' => 10,
                'collections' => [$featured],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'options' => ['Color' => ['Black', 'Silver']],
                'prices' => [14999, 14999],
                'weight' => 250,
                'inventory' => 25,
                'collections' => [$featured],
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'options' => [],
                'prices' => [1299],
                'weight' => 50,
                'inventory' => 200,
                'collections' => [$accessories],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'options' => ['Switch Type' => ['Red', 'Blue', 'Brown']],
                'prices' => [12999, 12999, 12999],
                'weight' => 1100,
                'inventory' => 15,
                'collections' => [$accessories],
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'options' => [],
                'prices' => [4999],
                'weight' => 2500,
                'inventory' => 30,
                'collections' => [$accessories],
            ],
        ];

        foreach ($electronicsProducts as $ep) {
            $product = Product::factory()->create([
                'store_id' => $store->id,
                'title' => $ep['title'],
                'handle' => $ep['handle'],
                'status' => ProductStatus::Active,
                'vendor' => $ep['vendor'],
                'product_type' => $ep['product_type'],
                'published_at' => now(),
            ]);

            $optionValues = [];
            $optionPosition = 0;
            foreach ($ep['options'] as $optionName => $values) {
                $option = ProductOption::factory()->create([
                    'product_id' => $product->id,
                    'name' => $optionName,
                    'position' => $optionPosition++,
                ]);

                foreach ($values as $vp => $val) {
                    $optionValues[] = ProductOptionValue::factory()->create([
                        'product_option_id' => $option->id,
                        'value' => $val,
                        'position' => $vp,
                    ]);
                }
            }

            if (empty($ep['options'])) {
                // Single default variant
                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(str_replace([' ', '-'], '', $ep['handle'])),
                    'price_amount' => $ep['prices'][0],
                    'currency' => 'EUR',
                    'weight_g' => $ep['weight'],
                    'is_default' => true,
                    'position' => 0,
                    'status' => VariantStatus::Active,
                ]);

                InventoryItem::factory()->create([
                    'store_id' => $store->id,
                    'variant_id' => $variant->id,
                    'quantity_on_hand' => $ep['inventory'],
                    'policy' => InventoryPolicy::Deny,
                ]);
            } else {
                foreach ($optionValues as $i => $ov) {
                    $variant = ProductVariant::factory()->create([
                        'product_id' => $product->id,
                        'sku' => strtoupper(str_replace([' ', '-'], '', $ep['handle'])).'-'.strtoupper(substr($ov->value, 0, 3)),
                        'price_amount' => $ep['prices'][$i] ?? $ep['prices'][0],
                        'currency' => 'EUR',
                        'weight_g' => $ep['weight'],
                        'is_default' => $i === 0,
                        'position' => $i,
                        'status' => VariantStatus::Active,
                    ]);

                    $variant->optionValues()->attach([$ov->id]);

                    InventoryItem::factory()->create([
                        'store_id' => $store->id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => $ep['inventory'],
                        'policy' => InventoryPolicy::Deny,
                    ]);
                }
            }

            foreach ($ep['collections'] as $ci => $col) {
                $col->products()->attach($product->id, ['position' => $ci]);
            }
        }
    }
}
