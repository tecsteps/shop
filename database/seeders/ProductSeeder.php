<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the demo product catalog: 20 Acme Fashion products and 5 Acme
     * Electronics products, each with options, variants, variant option value
     * pivots, inventory items, and collection assignments.
     */
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ($this->fashionProducts() as $definition) {
            $this->seedProduct($fashion, $definition);
        }

        foreach ($this->electronicsProducts() as $definition) {
            $this->seedProduct($electronics, $definition);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedProduct(Store $store, array $definition): void
    {
        $exists = Product::query()
            ->where('store_id', $store->getKey())
            ->where('handle', $definition['handle'])
            ->exists();

        if ($exists) {
            return;
        }

        $product = new Product([
            'title' => $definition['title'],
            'handle' => $definition['handle'],
            'status' => $definition['status'],
            'description_html' => $definition['description_html'],
            'vendor' => $definition['vendor'],
            'product_type' => $definition['product_type'],
            'tags' => $definition['tags'],
            'published_at' => $definition['published_at'],
        ]);
        $product->store_id = $store->getKey();
        $product->save();

        $valueModelsPerOption = [];

        foreach ($definition['options'] as $optionPosition => $option) {
            $productOption = $product->options()->create([
                'name' => $option['name'],
                'position' => $optionPosition,
            ]);

            $valueModels = [];

            foreach ($option['values'] as $valuePosition => $value) {
                $valueModels[] = $productOption->values()->create([
                    'value' => $value,
                    'position' => $valuePosition,
                ]);
            }

            $valueModelsPerOption[] = $valueModels;
        }

        $combinations = $this->cartesianProduct($valueModelsPerOption);

        foreach ($combinations as $position => $combination) {
            $override = $definition['variant_overrides'][$position] ?? [];

            $variant = $product->variants()->create([
                'sku' => $override['sku'] ?? $this->buildSku($definition['sku_prefix'], $combination),
                'price_amount' => $override['price_amount'] ?? $definition['price_amount'],
                'compare_at_amount' => $definition['compare_at_amount'] ?? null,
                'currency' => 'EUR',
                'weight_g' => $definition['weight_g'],
                'requires_shipping' => $definition['requires_shipping'] ?? true,
                'is_default' => $position === 0,
                'position' => $position,
                'status' => 'active',
            ]);

            if ($combination !== []) {
                $variant->optionValues()->attach(array_map(fn ($value) => $value->getKey(), $combination));
            }

            $variant->inventoryItem()->create([
                'store_id' => $store->getKey(),
                'quantity_on_hand' => $definition['inventory'],
                'quantity_reserved' => 0,
                'policy' => $definition['policy'] ?? 'deny',
            ]);
        }

        foreach ($definition['collections'] as $collectionHandle) {
            $collection = Collection::query()
                ->where('store_id', $store->getKey())
                ->where('handle', $collectionHandle)
                ->firstOrFail();

            $collection->products()->syncWithoutDetaching([
                $product->getKey() => ['position' => $collection->products()->count()],
            ]);
        }
    }

    /**
     * @param  list<\App\Models\ProductOptionValue>  $combination
     */
    private function buildSku(string $prefix, array $combination): string
    {
        if ($combination === []) {
            return $prefix;
        }

        $parts = array_map(
            fn ($value): string => preg_replace('/[^A-Z0-9]/', '', strtoupper($value->value)),
            $combination,
        );

        return $prefix.'-'.implode('-', $parts);
    }

    /**
     * @param  list<list<\App\Models\ProductOptionValue>>  $sets
     * @return list<list<\App\Models\ProductOptionValue>>
     */
    private function cartesianProduct(array $sets): array
    {
        $combinations = [[]];

        foreach ($sets as $set) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($set as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fashionProducts(): array
    {
        return [
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
                'sku_prefix' => 'ACME-CTSH',
                'price_amount' => 2499,
                'weight_g' => 200,
                'inventory' => 15,
            ],
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
                'sku_prefix' => 'ACME-JEAN',
                'price_amount' => 7999,
                'compare_at_amount' => 9999,
                'weight_g' => 800,
                'inventory' => 8,
            ],
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
                'sku_prefix' => 'ACME-HOOD',
                'price_amount' => 5999,
                'weight_g' => 500,
                'inventory' => 20,
            ],
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
                'sku_prefix' => 'ACME-BELT',
                'price_amount' => 3499,
                'weight_g' => 150,
                'inventory' => 25,
            ],
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
                'sku_prefix' => 'ACME-SNKR',
                'price_amount' => 11999,
                'weight_g' => 600,
                'inventory' => 5,
            ],
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
                'sku_prefix' => 'ACME-GTEE',
                'price_amount' => 2999,
                'weight_g' => 210,
                'inventory' => 18,
            ],
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
                'sku_prefix' => 'ACME-VNLT',
                'price_amount' => 3499,
                'weight_g' => 180,
                'inventory' => 12,
            ],
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
                'sku_prefix' => 'ACME-POLO',
                'price_amount' => 2799,
                'compare_at_amount' => 3999,
                'weight_g' => 250,
                'inventory' => 10,
            ],
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
                'sku_prefix' => 'ACME-CRGO',
                'price_amount' => 5499,
                'weight_g' => 700,
                'inventory' => 14,
            ],
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
                'sku_prefix' => 'ACME-CHSH',
                'price_amount' => 3999,
                'weight_g' => 350,
                'inventory' => 16,
            ],
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
                'sku_prefix' => 'ACME-WLTR',
                'price_amount' => 4999,
                'compare_at_amount' => 6999,
                'weight_g' => 550,
                'inventory' => 7,
            ],
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
                'sku_prefix' => 'ACME-SCRF',
                'price_amount' => 2999,
                'weight_g' => 120,
                'inventory' => 30,
            ],
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
                'sku_prefix' => 'ACME-TOTE',
                'price_amount' => 1999,
                'weight_g' => 300,
                'inventory' => 40,
            ],
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
                'sku_prefix' => 'ACME-BCKT',
                'price_amount' => 2499,
                'weight_g' => 80,
                'inventory' => 22,
            ],
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
                'sku_prefix' => 'ACME-WJKT',
                'price_amount' => 14999,
                'weight_g' => 900,
                'inventory' => 0,
            ],
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
                'sku_prefix' => 'ACME-RNCT',
                'price_amount' => 8999,
                'weight_g' => 400,
                'inventory' => 3,
            ],
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
                'sku_prefix' => 'ACME-LESN',
                'price_amount' => 15999,
                'weight_g' => 650,
                'inventory' => 0,
            ],
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
                'sku_prefix' => 'ACME-BDJK',
                'price_amount' => 9999,
                'weight_g' => 750,
                'inventory' => 0,
                'policy' => 'continue',
            ],
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
                'sku_prefix' => 'ACME-GIFT',
                'price_amount' => 2500,
                'weight_g' => 0,
                'requires_shipping' => false,
                'inventory' => 9999,
                'variant_overrides' => [
                    0 => ['sku' => 'ACME-GIFT-25', 'price_amount' => 2500],
                    1 => ['sku' => 'ACME-GIFT-50', 'price_amount' => 5000],
                    2 => ['sku' => 'ACME-GIFT-100', 'price_amount' => 10000],
                ],
            ],
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
                'sku_prefix' => 'ACME-OVCT',
                'price_amount' => 49999,
                'weight_g' => 1200,
                'inventory' => 3,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function electronicsProducts(): array
    {
        return [
            [
                'title' => 'Pro Laptop 15',
                'handle' => 'pro-laptop-15',
                'status' => 'active',
                'vendor' => 'TechCorp',
                'product_type' => 'Laptops',
                'tags' => ['new'],
                'description_html' => '<p>Powerful 15-inch laptop for professionals.</p>',
                'published_at' => now(),
                'collections' => ['featured'],
                'options' => [
                    ['name' => 'Storage', 'values' => ['256GB', '512GB', '1TB']],
                ],
                'sku_prefix' => 'TECH-LPT15',
                'price_amount' => 99999,
                'weight_g' => 1800,
                'inventory' => 10,
                'variant_overrides' => [
                    0 => ['price_amount' => 99999],
                    1 => ['price_amount' => 119999],
                    2 => ['price_amount' => 149999],
                ],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'status' => 'active',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['popular'],
                'description_html' => '<p>Premium wireless over-ear headphones with active noise cancellation.</p>',
                'published_at' => now(),
                'collections' => ['featured'],
                'options' => [
                    ['name' => 'Color', 'values' => ['Black', 'Silver']],
                ],
                'sku_prefix' => 'AUDIO-WHP',
                'price_amount' => 14999,
                'weight_g' => 250,
                'inventory' => 25,
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'status' => 'active',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => [],
                'description_html' => '<p>Durable braided USB-C cable, 2 meters long.</p>',
                'published_at' => now(),
                'collections' => ['accessories'],
                'options' => [],
                'sku_prefix' => 'CBL-USBC2M',
                'price_amount' => 1299,
                'weight_g' => 50,
                'inventory' => 200,
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'status' => 'active',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['trending'],
                'description_html' => '<p>Full-size mechanical keyboard with hot-swappable switches.</p>',
                'published_at' => now(),
                'collections' => ['featured'],
                'options' => [
                    ['name' => 'Switch Type', 'values' => ['Red', 'Blue', 'Brown']],
                ],
                'sku_prefix' => 'KEY-MECH',
                'price_amount' => 12999,
                'weight_g' => 1100,
                'inventory' => 15,
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'status' => 'active',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => [],
                'description_html' => '<p>Sturdy aluminium monitor stand with cable management.</p>',
                'published_at' => now(),
                'collections' => ['accessories'],
                'options' => [],
                'sku_prefix' => 'DESK-MST',
                'price_amount' => 4999,
                'weight_g' => 2500,
                'inventory' => 30,
            ],
        ];
    }
}
