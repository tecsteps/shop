<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    use SeedsDemoData;

    /**
     * Seed products with options, variants, inventory and collection
     * assignments for every store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedStore('acme-fashion', $this->fashionProducts(), $this->fashionCollectionAssignments());
            $this->seedStore('acme-electronics', $this->electronicsProducts(), $this->electronicsCollectionAssignments());
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<string, array<int, string>>  $collectionAssignments
     */
    private function seedStore(string $storeHandle, array $products, array $collectionAssignments): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['store_id' => $store->id, 'handle' => $data['handle']],
                [
                    'title' => $data['title'],
                    'status' => $data['status'],
                    'description_html' => '<p>'.$data['description'].'</p>',
                    'vendor' => $data['vendor'],
                    'product_type' => $data['product_type'],
                    'tags' => $data['tags'],
                    'published_at' => $this->resolveDate($data['published_at']),
                ],
            );

            $this->syncOptionsAndVariants($product, $store, $data);
        }

        $this->assignCollections($store, $collectionAssignments);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncOptionsAndVariants(Product $product, Store $store, array $data): void
    {
        $options = $data['options'] ?? [];
        $spec = $data['variants'];

        $optionValueIds = [];
        $optionPosition = 0;

        foreach ($options as $name => $values) {
            $option = ProductOption::updateOrCreate(
                ['product_id' => $product->id, 'position' => $optionPosition],
                ['name' => $name],
            );

            foreach (array_values($values) as $valuePosition => $value) {
                $optionValue = ProductOptionValue::updateOrCreate(
                    ['product_option_id' => $option->id, 'position' => $valuePosition],
                    ['value' => $value],
                );

                $optionValueIds[$name][$value] = $optionValue->id;
            }

            $optionPosition++;
        }

        $combinations = $this->buildCombinations($options);
        $skus = array_map(
            fn (array $combo): string => $this->buildSku($spec['sku'], $combo, $spec['abbr'] ?? []),
            $combinations,
        );

        // Remove variants that no longer belong to this product so re-runs stay clean.
        ProductVariant::where('product_id', $product->id)->whereNotIn('sku', $skus)->delete();

        foreach ($combinations as $variantPosition => $combo) {
            $sku = $skus[$variantPosition];

            $variant = ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'sku' => $sku],
                [
                    'price_amount' => (int) $this->resolvePerCombo($spec, 'price', $combo),
                    'compare_at_amount' => $this->resolvePerCombo($spec, 'compare_at', $combo),
                    'currency' => 'EUR',
                    'weight_g' => (int) $this->resolvePerCombo($spec, 'weight', $combo),
                    'requires_shipping' => (bool) $this->resolvePerCombo($spec, 'requires_shipping', $combo, true),
                    'is_default' => $variantPosition === 0,
                    'position' => $variantPosition,
                    'status' => 'active',
                ],
            );

            if ($combo !== []) {
                $valueIds = array_map(
                    fn (string $name): int => $optionValueIds[$name][$combo[$name]],
                    array_keys($options),
                );

                $variant->optionValues()->sync($valueIds);
            }

            InventoryItem::updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => (int) $this->resolvePerCombo($spec, 'inventory', $combo),
                    'quantity_reserved' => 0,
                    'policy' => (string) $this->resolvePerCombo($spec, 'policy', $combo, 'deny'),
                ],
            );
        }
    }

    /**
     * Build the cartesian product of option values in option order.
     *
     * @param  array<string, array<int, string>>  $options
     * @return array<int, array<string, string>>
     */
    private function buildCombinations(array $options): array
    {
        $combinations = [[]];

        foreach ($options as $name => $values) {
            $next = [];

            foreach ($combinations as $combo) {
                foreach ($values as $value) {
                    $next[] = $combo + [$name => $value];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param  array<string, string>  $combo
     * @param  array<string, array<string, string>>  $abbr
     */
    private function buildSku(string $template, array $combo, array $abbr): string
    {
        $sku = $template;

        foreach ($combo as $name => $value) {
            $sku = str_replace('{'.$name.'}', $abbr[$name][$value] ?? $value, $sku);
        }

        return $sku;
    }

    /**
     * Resolve a per-variant value, supporting uniform scalars or arrays keyed
     * by the first option value (e.g. gift card denominations).
     *
     * @param  array<string, mixed>  $spec
     * @param  array<string, string>  $combo
     */
    private function resolvePerCombo(array $spec, string $key, array $combo, mixed $default = null): mixed
    {
        $value = $spec[$key] ?? $default;

        if (! is_array($value) || $combo === []) {
            return $value;
        }

        $firstValue = reset($combo);

        return $value[$firstValue] ?? reset($value);
    }

    /**
     * @param  array<string, array<int, string>>  $assignments
     */
    private function assignCollections(Store $store, array $assignments): void
    {
        foreach ($assignments as $collectionHandle => $productHandles) {
            $collection = Collection::where('store_id', $store->id)->where('handle', $collectionHandle)->first();

            if ($collection === null) {
                continue;
            }

            $sync = [];
            $position = 0;

            foreach ($productHandles as $handle) {
                $product = Product::where('store_id', $store->id)->where('handle', $handle)->first();

                if ($product !== null) {
                    $sync[$product->id] = ['position' => $position++];
                }
            }

            $collection->products()->sync($sync);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
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
                'description' => 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L', 'XL'], 'Color' => ['White', 'Black', 'Navy']],
                'variants' => [
                    'sku' => 'ACME-CTSH-{Size}-{Color}',
                    'abbr' => ['Color' => ['White' => 'WHT', 'Black' => 'BLK', 'Navy' => 'NVY']],
                    'price' => 2499,
                    'weight' => 200,
                    'inventory' => 15,
                ],
            ],
            [
                'title' => 'Premium Slim Fit Jeans',
                'handle' => 'premium-slim-fit-jeans',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['new', 'sale'],
                'description' => 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.',
                'published_at' => 'now',
                'options' => ['Size' => ['28', '30', '32', '34', '36'], 'Color' => ['Blue', 'Black']],
                'variants' => [
                    'sku' => 'ACME-JEAN-{Size}-{Color}',
                    'abbr' => ['Color' => ['Blue' => 'BLU', 'Black' => 'BLK']],
                    'price' => 7999,
                    'compare_at' => 9999,
                    'weight' => 800,
                    'inventory' => 8,
                ],
            ],
            [
                'title' => 'Organic Hoodie',
                'handle' => 'organic-hoodie',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'Hoodies',
                'tags' => ['new', 'trending'],
                'description' => 'Made from 100% organic cotton. Warm, soft, and sustainably produced.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'variants' => [
                    'sku' => 'ACME-HOOD-{Size}',
                    'price' => 5999,
                    'weight' => 500,
                    'inventory' => 20,
                ],
            ],
            [
                'title' => 'Leather Belt',
                'handle' => 'leather-belt',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Genuine leather belt with brushed metal buckle. A wardrobe essential.',
                'published_at' => 'now',
                'options' => ['Size' => ['S/M', 'L/XL'], 'Color' => ['Brown', 'Black']],
                'variants' => [
                    'sku' => 'ACME-BELT-{Size}-{Color}',
                    'abbr' => ['Size' => ['S/M' => 'SM', 'L/XL' => 'LX'], 'Color' => ['Brown' => 'BRN', 'Black' => 'BLK']],
                    'price' => 3499,
                    'weight' => 150,
                    'inventory' => 25,
                ],
            ],
            [
                'title' => 'Running Sneakers',
                'handle' => 'running-sneakers',
                'status' => 'active',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['trending'],
                'description' => 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.',
                'published_at' => 'now',
                'options' => ['Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'], 'Color' => ['White', 'Black']],
                'variants' => [
                    'sku' => 'ACME-SNKR-{Size}-{Color}',
                    'abbr' => [
                        'Size' => ['EU 38' => 'EU38', 'EU 39' => 'EU39', 'EU 40' => 'EU40', 'EU 41' => 'EU41', 'EU 42' => 'EU42', 'EU 43' => 'EU43', 'EU 44' => 'EU44'],
                        'Color' => ['White' => 'WHT', 'Black' => 'BLK'],
                    ],
                    'price' => 11999,
                    'weight' => 600,
                    'inventory' => 5,
                ],
            ],
            [
                'title' => 'Graphic Print Tee',
                'handle' => 'graphic-print-tee',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['new'],
                'description' => 'Bold graphic print on soft cotton. Express yourself with this statement piece.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'variants' => [
                    'sku' => 'ACME-GPT-{Size}',
                    'price' => 2999,
                    'weight' => 210,
                    'inventory' => 18,
                ],
            ],
            [
                'title' => 'V-Neck Linen Tee',
                'handle' => 'v-neck-linen-tee',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['popular'],
                'description' => 'Lightweight linen blend v-neck. Perfect for warm summer days.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L'], 'Color' => ['Beige', 'Olive', 'Sky Blue']],
                'variants' => [
                    'sku' => 'ACME-LNTE-{Size}-{Color}',
                    'abbr' => ['Color' => ['Beige' => 'BGE', 'Olive' => 'OLV', 'Sky Blue' => 'SKY']],
                    'price' => 3499,
                    'weight' => 180,
                    'inventory' => 12,
                ],
            ],
            [
                'title' => 'Striped Polo Shirt',
                'handle' => 'striped-polo-shirt',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['sale'],
                'description' => 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'variants' => [
                    'sku' => 'ACME-POLO-{Size}',
                    'price' => 2799,
                    'compare_at' => 3999,
                    'weight' => 250,
                    'inventory' => 10,
                ],
            ],
            [
                'title' => 'Cargo Pants',
                'handle' => 'cargo-pants',
                'status' => 'active',
                'vendor' => 'Acme Workwear',
                'product_type' => 'Pants',
                'tags' => ['popular'],
                'description' => 'Utility cargo pants with multiple pockets. Durable cotton twill construction.',
                'published_at' => 'now',
                'options' => ['Size' => ['30', '32', '34', '36'], 'Color' => ['Khaki', 'Olive', 'Black']],
                'variants' => [
                    'sku' => 'ACME-CARGO-{Size}-{Color}',
                    'abbr' => ['Color' => ['Khaki' => 'KHK', 'Olive' => 'OLV', 'Black' => 'BLK']],
                    'price' => 5499,
                    'weight' => 700,
                    'inventory' => 14,
                ],
            ],
            [
                'title' => 'Chino Shorts',
                'handle' => 'chino-shorts',
                'status' => 'active',
                'vendor' => 'Acme Basics',
                'product_type' => 'Pants',
                'tags' => ['new', 'trending'],
                'description' => 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.',
                'published_at' => 'now',
                'options' => ['Size' => ['30', '32', '34', '36'], 'Color' => ['Navy', 'Sand']],
                'variants' => [
                    'sku' => 'ACME-CHINO-{Size}-{Color}',
                    'abbr' => ['Color' => ['Navy' => 'NVY', 'Sand' => 'SND']],
                    'price' => 3999,
                    'weight' => 350,
                    'inventory' => 16,
                ],
            ],
            [
                'title' => 'Wide Leg Trousers',
                'handle' => 'wide-leg-trousers',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Pants',
                'tags' => ['sale'],
                'description' => 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L']],
                'variants' => [
                    'sku' => 'ACME-WLT-{Size}',
                    'price' => 4999,
                    'compare_at' => 6999,
                    'weight' => 550,
                    'inventory' => 7,
                ],
            ],
            [
                'title' => 'Wool Scarf',
                'handle' => 'wool-scarf',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['popular'],
                'description' => 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.',
                'published_at' => 'now',
                'options' => ['Color' => ['Grey', 'Burgundy', 'Navy']],
                'variants' => [
                    'sku' => 'ACME-SCARF-{Color}',
                    'abbr' => ['Color' => ['Grey' => 'GRY', 'Burgundy' => 'BUR', 'Navy' => 'NVY']],
                    'price' => 2999,
                    'weight' => 120,
                    'inventory' => 30,
                ],
            ],
            [
                'title' => 'Canvas Tote Bag',
                'handle' => 'canvas-tote-bag',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['trending'],
                'description' => 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.',
                'published_at' => 'now',
                'options' => ['Color' => ['Natural', 'Black']],
                'variants' => [
                    'sku' => 'ACME-TOTE-{Color}',
                    'abbr' => ['Color' => ['Natural' => 'NAT', 'Black' => 'BLK']],
                    'price' => 1999,
                    'weight' => 300,
                    'inventory' => 40,
                ],
            ],
            [
                'title' => 'Bucket Hat',
                'handle' => 'bucket-hat',
                'status' => 'active',
                'vendor' => 'Acme Accessories',
                'product_type' => 'Accessories',
                'tags' => ['new', 'trending'],
                'description' => 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.',
                'published_at' => 'now',
                'options' => ['Size' => ['S/M', 'L/XL'], 'Color' => ['Beige', 'Black', 'Olive']],
                'variants' => [
                    'sku' => 'ACME-BHAT-{Size}-{Color}',
                    'abbr' => ['Size' => ['S/M' => 'SM', 'L/XL' => 'LX'], 'Color' => ['Beige' => 'BGE', 'Black' => 'BLK', 'Olive' => 'OLV']],
                    'price' => 2499,
                    'weight' => 80,
                    'inventory' => 22,
                ],
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
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'variants' => [
                    'sku' => 'ACME-WJKT-{Size}',
                    'price' => 14999,
                    'weight' => 900,
                    'inventory' => 0,
                ],
            ],
            [
                'title' => 'Discontinued Raincoat',
                'handle' => 'discontinued-raincoat',
                'status' => 'archived',
                'vendor' => 'Acme Outerwear',
                'product_type' => 'Jackets',
                'tags' => [],
                'description' => 'Lightweight waterproof raincoat. This product has been discontinued.',
                'published_at' => '6 months ago',
                'options' => ['Size' => ['M', 'L']],
                'variants' => [
                    'sku' => 'ACME-RCOAT-{Size}',
                    'price' => 8999,
                    'weight' => 400,
                    'inventory' => 3,
                ],
            ],
            [
                'title' => 'Limited Edition Sneakers',
                'handle' => 'limited-edition-sneakers',
                'status' => 'active',
                'vendor' => 'Acme Sport',
                'product_type' => 'Shoes',
                'tags' => ['limited'],
                'description' => 'Limited edition collaboration sneakers. Once they are gone, they are gone.',
                'published_at' => 'now',
                'options' => ['Size' => ['EU 40', 'EU 42', 'EU 44']],
                'variants' => [
                    'sku' => 'ACME-LE-SNKR-{Size}',
                    'abbr' => ['Size' => ['EU 40' => 'EU40', 'EU 42' => 'EU42', 'EU 44' => 'EU44']],
                    'price' => 15999,
                    'weight' => 650,
                    'inventory' => 0,
                ],
            ],
            [
                'title' => 'Backorder Denim Jacket',
                'handle' => 'backorder-denim-jacket',
                'status' => 'active',
                'vendor' => 'Acme Denim',
                'product_type' => 'Jackets',
                'tags' => ['popular'],
                'description' => 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L', 'XL']],
                'variants' => [
                    'sku' => 'ACME-DJKT-{Size}',
                    'price' => 9999,
                    'weight' => 750,
                    'inventory' => 0,
                    'policy' => 'continue',
                ],
            ],
            [
                'title' => 'Gift Card',
                'handle' => 'gift-card',
                'status' => 'active',
                'vendor' => 'Acme Fashion',
                'product_type' => 'Gift Cards',
                'tags' => ['popular'],
                'description' => 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.',
                'published_at' => 'now',
                'options' => ['Amount' => ['25 EUR', '50 EUR', '100 EUR']],
                'variants' => [
                    'sku' => 'ACME-GIFT-{Amount}',
                    'abbr' => ['Amount' => ['25 EUR' => '25', '50 EUR' => '50', '100 EUR' => '100']],
                    'price' => ['25 EUR' => 2500, '50 EUR' => 5000, '100 EUR' => 10000],
                    'weight' => 0,
                    'requires_shipping' => false,
                    'inventory' => 9999,
                ],
            ],
            [
                'title' => 'Cashmere Overcoat',
                'handle' => 'cashmere-overcoat',
                'status' => 'active',
                'vendor' => 'Acme Premium',
                'product_type' => 'Jackets',
                'tags' => ['limited', 'new'],
                'description' => 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.',
                'published_at' => 'now',
                'options' => ['Size' => ['S', 'M', 'L'], 'Color' => ['Camel', 'Charcoal']],
                'variants' => [
                    'sku' => 'ACME-OVER-{Size}-{Color}',
                    'abbr' => ['Color' => ['Camel' => 'CML', 'Charcoal' => 'CHR']],
                    'price' => 49999,
                    'weight' => 1200,
                    'inventory' => 3,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function fashionCollectionAssignments(): array
    {
        return [
            'new-arrivals' => ['classic-cotton-t-shirt', 'premium-slim-fit-jeans', 'organic-hoodie', 'running-sneakers', 'chino-shorts', 'bucket-hat', 'cashmere-overcoat'],
            't-shirts' => ['classic-cotton-t-shirt', 'graphic-print-tee', 'v-neck-linen-tee', 'striped-polo-shirt'],
            'pants-jeans' => ['premium-slim-fit-jeans', 'cargo-pants', 'chino-shorts', 'wide-leg-trousers'],
            'sale' => ['premium-slim-fit-jeans', 'striped-polo-shirt', 'wide-leg-trousers'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
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
                'description' => 'Professional 15-inch laptop with high-performance processor and stunning display.',
                'published_at' => 'now',
                'options' => ['Storage' => ['256GB', '512GB', '1TB']],
                'variants' => [
                    'sku' => 'TECH-LAP-{Storage}',
                    'price' => ['256GB' => 99999, '512GB' => 119999, '1TB' => 149999],
                    'weight' => 1800,
                    'inventory' => 10,
                ],
            ],
            [
                'title' => 'Wireless Headphones',
                'handle' => 'wireless-headphones',
                'status' => 'active',
                'vendor' => 'AudioMax',
                'product_type' => 'Audio',
                'tags' => ['trending'],
                'description' => 'Over-ear wireless headphones with active noise cancellation and 30-hour battery life.',
                'published_at' => 'now',
                'options' => ['Color' => ['Black', 'Silver']],
                'variants' => [
                    'sku' => 'AUDIO-WH-{Color}',
                    'abbr' => ['Color' => ['Black' => 'BLK', 'Silver' => 'SLV']],
                    'price' => 14999,
                    'weight' => 250,
                    'inventory' => 25,
                ],
            ],
            [
                'title' => 'USB-C Cable 2m',
                'handle' => 'usb-c-cable-2m',
                'status' => 'active',
                'vendor' => 'CablePro',
                'product_type' => 'Cables',
                'tags' => [],
                'description' => 'Durable braided USB-C to USB-C cable, two metres, supports fast charging and data transfer.',
                'published_at' => 'now',
                'variants' => [
                    'sku' => 'CABLE-USBC-2M',
                    'price' => 1299,
                    'weight' => 50,
                    'inventory' => 200,
                ],
            ],
            [
                'title' => 'Mechanical Keyboard',
                'handle' => 'mechanical-keyboard',
                'status' => 'active',
                'vendor' => 'KeyTech',
                'product_type' => 'Peripherals',
                'tags' => ['popular'],
                'description' => 'Tenkeyless mechanical keyboard with hot-swappable switches and RGB backlighting.',
                'published_at' => 'now',
                'options' => ['Switch Type' => ['Red', 'Blue', 'Brown']],
                'variants' => [
                    'sku' => 'KEY-MK-{Switch Type}',
                    'abbr' => ['Switch Type' => ['Red' => 'RED', 'Blue' => 'BLU', 'Brown' => 'BRN']],
                    'price' => 12999,
                    'weight' => 1100,
                    'inventory' => 15,
                ],
            ],
            [
                'title' => 'Monitor Stand',
                'handle' => 'monitor-stand',
                'status' => 'active',
                'vendor' => 'DeskGear',
                'product_type' => 'Accessories',
                'tags' => [],
                'description' => 'Adjustable aluminium monitor stand with cable management and a stable base.',
                'published_at' => 'now',
                'variants' => [
                    'sku' => 'DESK-STD-1',
                    'price' => 4999,
                    'weight' => 2500,
                    'inventory' => 30,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function electronicsCollectionAssignments(): array
    {
        return [
            'featured' => ['pro-laptop-15', 'wireless-headphones', 'mechanical-keyboard'],
            'accessories' => ['usb-c-cable-2m', 'monitor-stand'],
        ];
    }
}
