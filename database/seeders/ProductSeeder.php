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
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFashion();
        $this->seedElectronics();
    }

    private function seedFashion(): void
    {
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

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
                'options' => [
                    'Size' => ['S', 'M', 'L', 'XL'],
                    'Color' => ['White', 'Black', 'Navy'],
                ],
                'price' => 2499,
                'compare_at' => null,
                'weight_g' => 200,
                'inventory' => 15,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-CTSH',
                'collections' => ['new-arrivals', 't-shirts'],
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
                'options' => [
                    'Size' => ['28', '30', '32', '34', '36'],
                    'Color' => ['Blue', 'Black'],
                ],
                'price' => 7999,
                'compare_at' => 9999,
                'weight_g' => 800,
                'inventory' => 8,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-JEANS',
                'collections' => ['new-arrivals', 'pants-jeans', 'sale'],
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
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 5999,
                'compare_at' => null,
                'weight_g' => 500,
                'inventory' => 20,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-HOOD',
                'collections' => ['new-arrivals'],
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
                'options' => [
                    'Size' => ['S/M', 'L/XL'],
                    'Color' => ['Brown', 'Black'],
                ],
                'price' => 3499,
                'compare_at' => null,
                'weight_g' => 150,
                'inventory' => 25,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-BELT',
                'collections' => [],
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
                'options' => [
                    'Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'],
                    'Color' => ['White', 'Black'],
                ],
                'price' => 11999,
                'compare_at' => null,
                'weight_g' => 600,
                'inventory' => 5,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-SNKR',
                'collections' => ['new-arrivals'],
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
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 2999,
                'compare_at' => null,
                'weight_g' => 210,
                'inventory' => 18,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-GPTEE',
                'collections' => ['t-shirts'],
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
                'options' => [
                    'Size' => ['S', 'M', 'L'],
                    'Color' => ['Beige', 'Olive', 'Sky Blue'],
                ],
                'price' => 3499,
                'compare_at' => null,
                'weight_g' => 180,
                'inventory' => 12,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-VNLT',
                'collections' => ['t-shirts'],
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
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 2799,
                'compare_at' => 3999,
                'weight_g' => 250,
                'inventory' => 10,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-POLO',
                'collections' => ['t-shirts', 'sale'],
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
                'options' => [
                    'Size' => ['30', '32', '34', '36'],
                    'Color' => ['Khaki', 'Olive', 'Black'],
                ],
                'price' => 5499,
                'compare_at' => null,
                'weight_g' => 700,
                'inventory' => 14,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-CARGO',
                'collections' => ['pants-jeans'],
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
                'options' => [
                    'Size' => ['30', '32', '34', '36'],
                    'Color' => ['Navy', 'Sand'],
                ],
                'price' => 3999,
                'compare_at' => null,
                'weight_g' => 350,
                'inventory' => 16,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-CHINO',
                'collections' => ['pants-jeans', 'new-arrivals'],
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
                'options' => ['Size' => ['S', 'M', 'L']],
                'price' => 4999,
                'compare_at' => 6999,
                'weight_g' => 550,
                'inventory' => 7,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-WLT',
                'collections' => ['pants-jeans', 'sale'],
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
                'options' => ['Color' => ['Grey', 'Burgundy', 'Navy']],
                'price' => 2999,
                'compare_at' => null,
                'weight_g' => 120,
                'inventory' => 30,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-SCARF',
                'collections' => [],
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
                'options' => ['Color' => ['Natural', 'Black']],
                'price' => 1999,
                'compare_at' => null,
                'weight_g' => 300,
                'inventory' => 40,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-TOTE',
                'collections' => [],
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
                'options' => [
                    'Size' => ['S/M', 'L/XL'],
                    'Color' => ['Beige', 'Black', 'Olive'],
                ],
                'price' => 2499,
                'compare_at' => null,
                'weight_g' => 80,
                'inventory' => 22,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-BHAT',
                'collections' => ['new-arrivals'],
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
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 14999,
                'compare_at' => null,
                'weight_g' => 900,
                'inventory' => 0,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-WJKT',
                'collections' => [],
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
                'options' => ['Size' => ['M', 'L']],
                'price' => 8999,
                'compare_at' => null,
                'weight_g' => 400,
                'inventory' => 3,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-RAIN',
                'collections' => [],
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
                'options' => ['Size' => ['EU 40', 'EU 42', 'EU 44']],
                'price' => 15999,
                'compare_at' => null,
                'weight_g' => 650,
                'inventory' => 0,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-LTSNK',
                'collections' => [],
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
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'price' => 9999,
                'compare_at' => null,
                'weight_g' => 750,
                'inventory' => 0,
                'policy' => 'continue',
                'sku_prefix' => 'ACME-DJKT',
                'collections' => [],
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
                'options' => ['Amount' => ['25 EUR', '50 EUR', '100 EUR']],
                'price' => null,
                'compare_at' => null,
                'weight_g' => 0,
                'inventory' => 9999,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-GIFT',
                'collections' => [],
                'requires_shipping' => false,
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
                'options' => [
                    'Size' => ['S', 'M', 'L'],
                    'Color' => ['Camel', 'Charcoal'],
                ],
                'price' => 49999,
                'compare_at' => null,
                'weight_g' => 1200,
                'inventory' => 3,
                'policy' => 'deny',
                'sku_prefix' => 'ACME-CASH',
                'collections' => ['new-arrivals'],
            ],
        ];

        foreach ($products as $data) {
            $this->createProduct($store, $data);
        }
    }

    private function seedElectronics(): void
    {
        $store = Store::where('slug', 'acme-electronics')->firstOrFail();

        $products = [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'status' => ProductStatus::Active,
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['popular'],
                'description_html' => '<p>Professional-grade 15-inch laptop with high-performance specs.</p>',
                'published_at' => now(),
                'options' => ['Storage' => ['256GB', '512GB', '1TB']],
                'price' => null,
                'compare_at' => null,
                'weight_g' => 1800,
                'inventory' => 10,
                'policy' => 'deny',
                'sku_prefix' => 'TECH-LAP',
                'collections' => ['featured'],
                'variant_prices' => [99999, 119999, 149999],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'status' => ProductStatus::Active,
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['trending'],
                'description_html' => '<p>Premium wireless headphones with active noise cancellation.</p>',
                'published_at' => now(),
                'options' => ['Color' => ['Black', 'Silver']],
                'price' => 14999,
                'compare_at' => null,
                'weight_g' => 250,
                'inventory' => 25,
                'policy' => 'deny',
                'sku_prefix' => 'AUDIO-HP',
                'collections' => ['featured'],
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'status' => ProductStatus::Active,
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => [],
                'description_html' => '<p>High-quality USB-C cable, 2 meters, supports fast charging.</p>',
                'published_at' => now(),
                'options' => [],
                'price' => 1299,
                'compare_at' => null,
                'weight_g' => 50,
                'inventory' => 200,
                'policy' => 'deny',
                'sku_prefix' => 'CABLE-USBC',
                'collections' => ['accessories'],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'status' => ProductStatus::Active,
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['popular'],
                'description_html' => '<p>Full-size mechanical keyboard with customizable RGB lighting.</p>',
                'published_at' => now(),
                'options' => ['Switch Type' => ['Red', 'Blue', 'Brown']],
                'price' => 12999,
                'compare_at' => null,
                'weight_g' => 1100,
                'inventory' => 15,
                'policy' => 'deny',
                'sku_prefix' => 'KEY-MECH',
                'collections' => ['featured'],
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'status' => ProductStatus::Active,
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => [],
                'description_html' => '<p>Ergonomic monitor stand with adjustable height and cable management.</p>',
                'published_at' => now(),
                'options' => [],
                'price' => 4999,
                'compare_at' => null,
                'weight_g' => 2500,
                'inventory' => 30,
                'policy' => 'deny',
                'sku_prefix' => 'DESK-STAND',
                'collections' => ['accessories'],
            ],
        ];

        foreach ($products as $data) {
            $this->createProduct($store, $data);
        }
    }

    private function createProduct(Store $store, array $data): Product
    {
        $product = Product::firstOrCreate(
            ['store_id' => $store->id, 'handle' => $data['handle']],
            [
                'title' => $data['title'],
                'status' => $data['status'],
                'vendor' => $data['vendor'],
                'product_type' => $data['product_type'],
                'tags' => $data['tags'],
                'description_html' => $data['description_html'],
                'published_at' => $data['published_at'],
            ]
        );

        if ($product->variants()->exists()) {
            return $product;
        }

        $options = $data['options'];
        $requiresShipping = $data['requires_shipping'] ?? true;
        $policy = InventoryPolicy::from($data['policy']);

        if (empty($options)) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $data['sku_prefix'].'-DEFAULT',
                'price_amount' => $data['price'],
                'compare_at_amount' => $data['compare_at'],
                'currency' => 'EUR',
                'weight_g' => $data['weight_g'],
                'requires_shipping' => $requiresShipping,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ]);

            InventoryItem::create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $data['inventory'],
                'quantity_reserved' => 0,
                'policy' => $policy,
            ]);

            return $product;
        }

        $optionModels = [];
        $optionValueModels = [];
        $optPos = 0;
        foreach ($options as $optName => $values) {
            $option = ProductOption::create([
                'product_id' => $product->id,
                'name' => $optName,
                'position' => $optPos++,
            ]);
            $optionModels[$optName] = $option;
            $valPos = 0;
            foreach ($values as $val) {
                $ov = ProductOptionValue::create([
                    'product_option_id' => $option->id,
                    'value' => $val,
                    'position' => $valPos++,
                ]);
                $optionValueModels[$optName][$val] = $ov;
            }
        }

        $combinations = $this->cartesianProduct($options);
        $variantPrices = $data['variant_prices'] ?? null;
        $giftCardPrices = $data['gift_card_prices'] ?? null;
        $position = 0;

        foreach ($combinations as $index => $combo) {
            $skuParts = [];
            foreach ($combo as $val) {
                $skuParts[] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $val), 0, 3));
            }
            $sku = $data['sku_prefix'].'-'.implode('-', $skuParts);

            $price = $data['price'];
            if ($variantPrices !== null) {
                $price = $variantPrices[$index] ?? $data['price'];
            }
            if ($giftCardPrices !== null) {
                $price = $giftCardPrices[$index] ?? $data['price'];
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'price_amount' => $price,
                'compare_at_amount' => $data['compare_at'],
                'currency' => 'EUR',
                'weight_g' => $data['weight_g'],
                'requires_shipping' => $requiresShipping,
                'is_default' => $position === 0,
                'position' => $position++,
                'status' => VariantStatus::Active,
            ]);

            $optionNames = array_keys($options);
            foreach ($combo as $i => $val) {
                $optName = $optionNames[$i];
                $ov = $optionValueModels[$optName][$val];
                DB::table('variant_option_values')->insert([
                    'variant_id' => $variant->id,
                    'product_option_value_id' => $ov->id,
                ]);
            }

            InventoryItem::create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $data['inventory'],
                'quantity_reserved' => 0,
                'policy' => $policy,
            ]);
        }

        $collectionHandles = $data['collections'] ?? [];
        foreach ($collectionHandles as $pos => $handle) {
            $collection = Collection::where('store_id', $store->id)->where('handle', $handle)->first();
            if ($collection && ! $collection->products()->where('product_id', $product->id)->exists()) {
                $collection->products()->attach($product->id, ['position' => $collection->products()->count()]);
            }
        }

        return $product;
    }

    /**
     * @param  array<string, array<string>>  $options
     * @return array<array<string>>
     */
    private function cartesianProduct(array $options): array
    {
        $values = array_values($options);
        if (empty($values)) {
            return [[]];
        }

        $result = [[]];
        foreach ($values as $group) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($group as $val) {
                    $newResult[] = array_merge($existing, [$val]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }
}
