<?php

namespace Database\Seeders;

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
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        $this->seedFashionProducts($fashion);
        $this->seedElectronicsProducts($electronics);
    }

    protected function seedFashionProducts(Store $store): void
    {
        $collections = Collection::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        // Product 1: Classic Cotton T-Shirt
        $p1 = $this->createProduct($store, [
            'title' => 'Classic Cotton T-Shirt',
            'handle' => 'classic-cotton-t-shirt',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Basics',
            'product_type' => 'T-Shirts',
            'tags' => ['new', 'popular'],
            'description_html' => '<p>A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p1, [
            'Size' => ['S', 'M', 'L', 'XL'],
            'Color' => ['White', 'Black', 'Navy'],
        ], 2499, null, 200, 15, 'deny', 'ACME-CTSH');
        $this->attachToCollections($p1, $collections, ['new-arrivals', 't-shirts']);

        // Product 2: Premium Slim Fit Jeans
        $p2 = $this->createProduct($store, [
            'title' => 'Premium Slim Fit Jeans',
            'handle' => 'premium-slim-fit-jeans',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Denim',
            'product_type' => 'Pants',
            'tags' => ['new', 'sale'],
            'description_html' => '<p>Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p2, [
            'Size' => ['28', '30', '32', '34', '36'],
            'Color' => ['Blue', 'Black'],
        ], 7999, 9999, 800, 8, 'deny', 'ACME-JEANS');
        $this->attachToCollections($p2, $collections, ['new-arrivals', 'pants-jeans', 'sale']);

        // Product 3: Organic Hoodie
        $p3 = $this->createProduct($store, [
            'title' => 'Organic Hoodie',
            'handle' => 'organic-hoodie',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Basics',
            'product_type' => 'Hoodies',
            'tags' => ['new', 'trending'],
            'description_html' => '<p>Made from 100% organic cotton. Warm, soft, and sustainably produced.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p3, [
            'Size' => ['S', 'M', 'L', 'XL'],
        ], 5999, null, 500, 20, 'deny', 'ACME-HOOD');
        $this->attachToCollections($p3, $collections, ['new-arrivals']);

        // Product 4: Leather Belt
        $p4 = $this->createProduct($store, [
            'title' => 'Leather Belt',
            'handle' => 'leather-belt',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Accessories',
            'product_type' => 'Accessories',
            'tags' => ['popular'],
            'description_html' => '<p>Genuine leather belt with brushed metal buckle. A wardrobe essential.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p4, [
            'Size' => ['S/M', 'L/XL'],
            'Color' => ['Brown', 'Black'],
        ], 3499, null, 150, 25, 'deny', 'ACME-BELT');

        // Product 5: Running Sneakers
        $p5 = $this->createProduct($store, [
            'title' => 'Running Sneakers',
            'handle' => 'running-sneakers',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Sport',
            'product_type' => 'Shoes',
            'tags' => ['trending'],
            'description_html' => '<p>Lightweight running sneakers with responsive cushioning and breathable mesh upper.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p5, [
            'Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'],
            'Color' => ['White', 'Black'],
        ], 11999, null, 600, 5, 'deny', 'ACME-RUN');
        $this->attachToCollections($p5, $collections, ['new-arrivals']);

        // Product 6: Graphic Print Tee
        $p6 = $this->createProduct($store, [
            'title' => 'Graphic Print Tee',
            'handle' => 'graphic-print-tee',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Basics',
            'product_type' => 'T-Shirts',
            'tags' => ['new'],
            'description_html' => '<p>Bold graphic print on soft cotton. Express yourself with this statement piece.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p6, [
            'Size' => ['S', 'M', 'L', 'XL'],
        ], 2999, null, 210, 18, 'deny', 'ACME-GPT');
        $this->attachToCollections($p6, $collections, ['t-shirts']);

        // Product 7: V-Neck Linen Tee
        $p7 = $this->createProduct($store, [
            'title' => 'V-Neck Linen Tee',
            'handle' => 'v-neck-linen-tee',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Basics',
            'product_type' => 'T-Shirts',
            'tags' => ['popular'],
            'description_html' => '<p>Lightweight linen blend v-neck. Perfect for warm summer days.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p7, [
            'Size' => ['S', 'M', 'L'],
            'Color' => ['Beige', 'Olive', 'Sky Blue'],
        ], 3499, null, 180, 12, 'deny', 'ACME-VNK');
        $this->attachToCollections($p7, $collections, ['t-shirts']);

        // Product 8: Striped Polo Shirt
        $p8 = $this->createProduct($store, [
            'title' => 'Striped Polo Shirt',
            'handle' => 'striped-polo-shirt',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Basics',
            'product_type' => 'T-Shirts',
            'tags' => ['sale'],
            'description_html' => '<p>Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p8, [
            'Size' => ['S', 'M', 'L', 'XL'],
        ], 2799, 3999, 250, 10, 'deny', 'ACME-POLO');
        $this->attachToCollections($p8, $collections, ['t-shirts', 'sale']);

        // Product 9: Cargo Pants
        $p9 = $this->createProduct($store, [
            'title' => 'Cargo Pants',
            'handle' => 'cargo-pants',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Workwear',
            'product_type' => 'Pants',
            'tags' => ['popular'],
            'description_html' => '<p>Utility cargo pants with multiple pockets. Durable cotton twill construction.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p9, [
            'Size' => ['30', '32', '34', '36'],
            'Color' => ['Khaki', 'Olive', 'Black'],
        ], 5499, null, 700, 14, 'deny', 'ACME-CRGO');
        $this->attachToCollections($p9, $collections, ['pants-jeans']);

        // Product 10: Chino Shorts
        $p10 = $this->createProduct($store, [
            'title' => 'Chino Shorts',
            'handle' => 'chino-shorts',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Basics',
            'product_type' => 'Pants',
            'tags' => ['new', 'trending'],
            'description_html' => '<p>Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p10, [
            'Size' => ['30', '32', '34', '36'],
            'Color' => ['Navy', 'Sand'],
        ], 3999, null, 350, 16, 'deny', 'ACME-CHNO');
        $this->attachToCollections($p10, $collections, ['pants-jeans', 'new-arrivals']);

        // Product 11: Wide Leg Trousers
        $p11 = $this->createProduct($store, [
            'title' => 'Wide Leg Trousers',
            'handle' => 'wide-leg-trousers',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Denim',
            'product_type' => 'Pants',
            'tags' => ['sale'],
            'description_html' => '<p>Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p11, [
            'Size' => ['S', 'M', 'L'],
        ], 4999, 6999, 550, 7, 'deny', 'ACME-WLT');
        $this->attachToCollections($p11, $collections, ['pants-jeans', 'sale']);

        // Product 12: Wool Scarf
        $p12 = $this->createProduct($store, [
            'title' => 'Wool Scarf',
            'handle' => 'wool-scarf',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Accessories',
            'product_type' => 'Accessories',
            'tags' => ['popular'],
            'description_html' => '<p>Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p12, [
            'Color' => ['Grey', 'Burgundy', 'Navy'],
        ], 2999, null, 120, 30, 'deny', 'ACME-WSCF');

        // Product 13: Canvas Tote Bag
        $p13 = $this->createProduct($store, [
            'title' => 'Canvas Tote Bag',
            'handle' => 'canvas-tote-bag',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Accessories',
            'product_type' => 'Accessories',
            'tags' => ['trending'],
            'description_html' => '<p>Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p13, [
            'Color' => ['Natural', 'Black'],
        ], 1999, null, 300, 40, 'deny', 'ACME-TOTE');

        // Product 14: Bucket Hat
        $p14 = $this->createProduct($store, [
            'title' => 'Bucket Hat',
            'handle' => 'bucket-hat',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Accessories',
            'product_type' => 'Accessories',
            'tags' => ['new', 'trending'],
            'description_html' => '<p>Lightweight bucket hat for sun protection. Packable design, washed cotton twill.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p14, [
            'Size' => ['S/M', 'L/XL'],
            'Color' => ['Beige', 'Black', 'Olive'],
        ], 2499, null, 80, 22, 'deny', 'ACME-BHAT');
        $this->attachToCollections($p14, $collections, ['new-arrivals']);

        // Product 15: Unreleased Winter Jacket (DRAFT)
        $p15 = $this->createProduct($store, [
            'title' => 'Unreleased Winter Jacket',
            'handle' => 'unreleased-winter-jacket',
            'status' => ProductStatus::Draft,
            'vendor' => 'Acme Outerwear',
            'product_type' => 'Jackets',
            'tags' => ['limited'],
            'description_html' => '<p>Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.</p>',
            'published_at' => null,
        ]);
        $this->createVariantsFromOptions($store, $p15, [
            'Size' => ['S', 'M', 'L', 'XL'],
        ], 14999, null, 900, 0, 'deny', 'ACME-WJKT');

        // Product 16: Discontinued Raincoat (ARCHIVED)
        $p16 = $this->createProduct($store, [
            'title' => 'Discontinued Raincoat',
            'handle' => 'discontinued-raincoat',
            'status' => ProductStatus::Archived,
            'vendor' => 'Acme Outerwear',
            'product_type' => 'Jackets',
            'tags' => [],
            'description_html' => '<p>Lightweight waterproof raincoat. This product has been discontinued.</p>',
            'published_at' => now()->subMonths(6),
        ]);
        $this->createVariantsFromOptions($store, $p16, [
            'Size' => ['M', 'L'],
        ], 8999, null, 400, 3, 'deny', 'ACME-RAIN');

        // Product 17: Limited Edition Sneakers (SOLD OUT)
        $p17 = $this->createProduct($store, [
            'title' => 'Limited Edition Sneakers',
            'handle' => 'limited-edition-sneakers',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Sport',
            'product_type' => 'Shoes',
            'tags' => ['limited'],
            'description_html' => '<p>Limited edition collaboration sneakers. Once they are gone, they are gone.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p17, [
            'Size' => ['EU 40', 'EU 42', 'EU 44'],
        ], 15999, null, 650, 0, 'deny', 'ACME-LSNE');

        // Product 18: Backorder Denim Jacket (CONTINUE policy)
        $p18 = $this->createProduct($store, [
            'title' => 'Backorder Denim Jacket',
            'handle' => 'backorder-denim-jacket',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Denim',
            'product_type' => 'Jackets',
            'tags' => ['popular'],
            'description_html' => '<p>Classic denim jacket. Currently on backorder - ships within 2-3 weeks.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p18, [
            'Size' => ['S', 'M', 'L', 'XL'],
        ], 9999, null, 750, 0, 'continue', 'ACME-DJKT');

        // Product 19: Gift Card (DIGITAL)
        $p19 = $this->createProduct($store, [
            'title' => 'Gift Card',
            'handle' => 'gift-card',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Fashion',
            'product_type' => 'Gift Cards',
            'tags' => ['popular'],
            'description_html' => '<p>Digital gift card delivered via email. The perfect gift when you are not sure what to choose.</p>',
        ]);
        $this->createGiftCardVariants($store, $p19);

        // Product 20: Cashmere Overcoat (EXPENSIVE)
        $p20 = $this->createProduct($store, [
            'title' => 'Cashmere Overcoat',
            'handle' => 'cashmere-overcoat',
            'status' => ProductStatus::Active,
            'vendor' => 'Acme Premium',
            'product_type' => 'Jackets',
            'tags' => ['limited', 'new'],
            'description_html' => '<p>Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.</p>',
        ]);
        $this->createVariantsFromOptions($store, $p20, [
            'Size' => ['S', 'M', 'L'],
            'Color' => ['Camel', 'Charcoal'],
        ], 49999, null, 1200, 3, 'deny', 'ACME-CASH');
        $this->attachToCollections($p20, $collections, ['new-arrivals']);
    }

    protected function seedElectronicsProducts(Store $store): void
    {
        $collections = Collection::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->pluck('id', 'handle');

        // E1: Pro Laptop 15
        $e1 = $this->createProduct($store, [
            'title' => 'Pro Laptop 15',
            'handle' => 'pro-laptop-15',
            'status' => ProductStatus::Active,
            'vendor' => 'TechCorp',
            'product_type' => 'Laptops',
            'tags' => ['popular'],
            'description_html' => '<p>Professional 15-inch laptop with powerful performance and long battery life.</p>',
        ]);
        $this->createElectronicsVariants($store, $e1, 'Storage', [
            ['value' => '256GB', 'price' => 99999],
            ['value' => '512GB', 'price' => 119999],
            ['value' => '1TB', 'price' => 149999],
        ], 1800, 10, 'ACME-LAP');
        $this->attachToCollections($e1, $collections, ['featured']);

        // E2: Wireless Headphones
        $e2 = $this->createProduct($store, [
            'title' => 'Wireless Headphones',
            'handle' => 'wireless-headphones',
            'status' => ProductStatus::Active,
            'vendor' => 'AudioMax',
            'product_type' => 'Audio',
            'tags' => ['trending'],
            'description_html' => '<p>Premium wireless headphones with active noise cancellation and 30-hour battery life.</p>',
        ]);
        $this->createVariantsFromOptions($store, $e2, [
            'Color' => ['Black', 'Silver'],
        ], 14999, null, 250, 25, 'deny', 'ACME-WHPH');
        $this->attachToCollections($e2, $collections, ['featured']);

        // E3: USB-C Cable 2m
        $e3 = $this->createProduct($store, [
            'title' => 'USB-C Cable 2m',
            'handle' => 'usb-c-cable-2m',
            'status' => ProductStatus::Active,
            'vendor' => 'CablePro',
            'product_type' => 'Cables',
            'tags' => ['popular'],
            'description_html' => '<p>High-quality USB-C cable, 2 meters long. Supports fast charging and data transfer.</p>',
        ]);
        $this->createSingleVariant($store, $e3, 1299, 50, 200, 'ACME-USBC');
        $this->attachToCollections($e3, $collections, ['accessories']);

        // E4: Mechanical Keyboard
        $e4 = $this->createProduct($store, [
            'title' => 'Mechanical Keyboard',
            'handle' => 'mechanical-keyboard',
            'status' => ProductStatus::Active,
            'vendor' => 'KeyTech',
            'product_type' => 'Peripherals',
            'tags' => ['new'],
            'description_html' => '<p>Full-size mechanical keyboard with customizable RGB backlighting.</p>',
        ]);
        $this->createVariantsFromOptions($store, $e4, [
            'Switch Type' => ['Red', 'Blue', 'Brown'],
        ], 12999, null, 1100, 15, 'deny', 'ACME-MKBD');
        $this->attachToCollections($e4, $collections, ['featured']);

        // E5: Monitor Stand
        $e5 = $this->createProduct($store, [
            'title' => 'Monitor Stand',
            'handle' => 'monitor-stand',
            'status' => ProductStatus::Active,
            'vendor' => 'DeskGear',
            'product_type' => 'Accessories',
            'tags' => ['popular'],
            'description_html' => '<p>Adjustable monitor stand with cable management. Supports monitors up to 32 inches.</p>',
        ]);
        $this->createSingleVariant($store, $e5, 4999, 2500, 30, 'ACME-MSTD');
        $this->attachToCollections($e5, $collections, ['accessories']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createProduct(Store $store, array $attributes): Product
    {
        $defaults = [
            'store_id' => $store->id,
            'published_at' => now(),
        ];

        return Product::factory()->create(array_merge($defaults, $attributes));
    }

    /**
     * @param  array<string, string[]>  $options
     */
    protected function createVariantsFromOptions(
        Store $store,
        Product $product,
        array $options,
        int $price,
        ?int $compareAt,
        int $weightG,
        int $inventory,
        string $policy,
        string $skuPrefix,
    ): void {
        $optionValues = [];
        $optionPosition = 0;

        foreach ($options as $optionName => $values) {
            $option = ProductOption::factory()->create([
                'product_id' => $product->id,
                'name' => $optionName,
                'position' => $optionPosition,
            ]);

            $optionValues[$optionName] = [];
            foreach ($values as $i => $value) {
                $optionValues[$optionName][] = ProductOptionValue::factory()->create([
                    'product_option_id' => $option->id,
                    'value' => $value,
                    'position' => $i,
                ]);
            }

            $optionPosition++;
        }

        $optionNames = array_keys($options);
        $combinations = $this->generateCombinations($optionValues, $optionNames);
        $position = 0;

        foreach ($combinations as $combo) {
            $skuParts = [];
            $valueIds = [];
            foreach ($combo as $optionValue) {
                $skuParts[] = $this->abbreviate($optionValue->value);
                $valueIds[] = $optionValue->id;
            }

            $sku = $skuPrefix.'-'.implode('-', $skuParts);

            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => $sku,
                'price_amount' => $price,
                'compare_at_amount' => $compareAt,
                'currency' => $store->default_currency,
                'weight_g' => $weightG,
                'requires_shipping' => true,
                'is_default' => $position === 0,
                'position' => $position,
            ]);

            $variant->optionValues()->attach($valueIds);

            InventoryItem::factory()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $inventory,
                'quantity_reserved' => 0,
                'policy' => $policy,
            ]);

            $position++;
        }
    }

    protected function createSingleVariant(Store $store, Product $product, int $price, int $weightG, int $inventory, string $sku): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => $sku,
            'price_amount' => $price,
            'currency' => $store->default_currency,
            'weight_g' => $weightG,
            'requires_shipping' => true,
            'is_default' => true,
            'position' => 0,
        ]);

        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'variant_id' => $variant->id,
            'quantity_on_hand' => $inventory,
            'quantity_reserved' => 0,
            'policy' => 'deny',
        ]);
    }

    protected function createGiftCardVariants(Store $store, Product $product): void
    {
        $option = ProductOption::factory()->create([
            'product_id' => $product->id,
            'name' => 'Amount',
            'position' => 0,
        ]);

        $denominations = [
            ['value' => '25 EUR', 'price' => 2500, 'sku' => 'ACME-GIFT-25'],
            ['value' => '50 EUR', 'price' => 5000, 'sku' => 'ACME-GIFT-50'],
            ['value' => '100 EUR', 'price' => 10000, 'sku' => 'ACME-GIFT-100'],
        ];

        foreach ($denominations as $i => $denom) {
            $optionValue = ProductOptionValue::factory()->create([
                'product_option_id' => $option->id,
                'value' => $denom['value'],
                'position' => $i,
            ]);

            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => $denom['sku'],
                'price_amount' => $denom['price'],
                'currency' => $store->default_currency,
                'weight_g' => 0,
                'requires_shipping' => false,
                'is_default' => $i === 0,
                'position' => $i,
            ]);

            $variant->optionValues()->attach([$optionValue->id]);

            InventoryItem::factory()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => 9999,
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ]);
        }
    }

    /**
     * @param  array<string, mixed[]>  $options
     */
    protected function createElectronicsVariants(
        Store $store,
        Product $product,
        string $optionName,
        array $variants,
        int $weightG,
        int $inventory,
        string $skuPrefix,
    ): void {
        $option = ProductOption::factory()->create([
            'product_id' => $product->id,
            'name' => $optionName,
            'position' => 0,
        ]);

        foreach ($variants as $i => $data) {
            $optionValue = ProductOptionValue::factory()->create([
                'product_option_id' => $option->id,
                'value' => $data['value'],
                'position' => $i,
            ]);

            $sku = $skuPrefix.'-'.$this->abbreviate($data['value']);
            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'sku' => $sku,
                'price_amount' => $data['price'],
                'currency' => $store->default_currency,
                'weight_g' => $weightG,
                'requires_shipping' => true,
                'is_default' => $i === 0,
                'position' => $i,
            ]);

            $variant->optionValues()->attach([$optionValue->id]);

            InventoryItem::factory()->create([
                'store_id' => $store->id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $inventory,
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ]);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $collections
     * @param  string[]  $handles
     */
    protected function attachToCollections(Product $product, $collections, array $handles): void
    {
        foreach ($handles as $position => $handle) {
            if ($collections->has($handle)) {
                $collectionId = $collections->get($handle);
                $existingCount = \Illuminate\Support\Facades\DB::table('collection_products')
                    ->where('collection_id', $collectionId)
                    ->count();
                \Illuminate\Support\Facades\DB::table('collection_products')->insert([
                    'collection_id' => $collectionId,
                    'product_id' => $product->id,
                    'position' => $existingCount,
                ]);
            }
        }
    }

    /**
     * @param  array<string, ProductOptionValue[]>  $optionValues
     * @param  string[]  $optionNames
     * @return array<int, ProductOptionValue[]>
     */
    protected function generateCombinations(array $optionValues, array $optionNames, int $index = 0, array $current = []): array
    {
        if ($index >= count($optionNames)) {
            return [$current];
        }

        $results = [];
        $name = $optionNames[$index];

        foreach ($optionValues[$name] as $value) {
            $results = array_merge(
                $results,
                $this->generateCombinations($optionValues, $optionNames, $index + 1, array_merge($current, [$value]))
            );
        }

        return $results;
    }

    protected function abbreviate(string $value): string
    {
        $map = [
            'White' => 'WHT', 'Black' => 'BLK', 'Navy' => 'NVY',
            'Blue' => 'BLU', 'Red' => 'RED', 'Green' => 'GRN',
            'Grey' => 'GRY', 'Burgundy' => 'BUR', 'Beige' => 'BEI',
            'Olive' => 'OLV', 'Sky Blue' => 'SKB', 'Brown' => 'BRN',
            'Natural' => 'NAT', 'Khaki' => 'KHK', 'Sand' => 'SND',
            'Camel' => 'CML', 'Charcoal' => 'CHR', 'Silver' => 'SLV',
            'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL',
            'S/M' => 'SM', 'L/XL' => 'LXL',
            '28' => '28', '30' => '30', '32' => '32', '34' => '34', '36' => '36',
            'EU 38' => 'EU38', 'EU 39' => 'EU39', 'EU 40' => 'EU40',
            'EU 41' => 'EU41', 'EU 42' => 'EU42', 'EU 43' => 'EU43', 'EU 44' => 'EU44',
            '256GB' => '256', '512GB' => '512', '1TB' => '1TB',
            'Red' => 'RED', 'Blue' => 'BLU', 'Brown' => 'BRN',
            '25 EUR' => '25', '50 EUR' => '50', '100 EUR' => '100',
        ];

        return $map[$value] ?? strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $value), 0, 3));
    }
}
