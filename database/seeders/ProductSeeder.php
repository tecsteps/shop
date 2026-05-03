<?php

namespace Database\Seeders;

use App\Models\Collection as ProductCollection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedProducts('acme-fashion', $this->fashionProducts());
            $this->seedProducts('acme-electronics', $this->electronicsProducts());
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    private function seedProducts(string $storeHandle, array $products): void
    {
        $store = Store::query()->where('handle', $storeHandle)->firstOrFail();
        $collections = ProductCollection::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->get()
            ->keyBy('handle');

        foreach ($products as $index => $data) {
            $product = Product::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'handle' => $data['handle'],
                ],
                [
                    ...Arr::only($data, ['title', 'status', 'description_html', 'vendor', 'product_type', 'tags', 'published_at']),
                    'store_id' => $store->getKey(),
                ],
            );

            $this->replaceCatalogChildren($product);

            $optionValueIds = $this->createOptions($product, $data['options'] ?? []);
            $this->createVariants($store, $product, $data, $optionValueIds);

            $collectionIds = collect($data['collections'] ?? [])
                ->map(fn (string $handle): ?int => $collections->get($handle)?->getKey())
                ->filter()
                ->mapWithKeys(fn (int $collectionId): array => [$collectionId => ['position' => $index]])
                ->all();

            $product->collections()->sync($collectionIds);
        }
    }

    private function replaceCatalogChildren(Product $product): void
    {
        ProductMedia::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->get()
            ->each
            ->delete();

        ProductVariant::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->get()
            ->each
            ->delete();

        ProductOption::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->delete();
    }

    /**
     * @param  array<string, array<int, string>>  $options
     * @return array<string, array<string, int>>
     */
    private function createOptions(Product $product, array $options): array
    {
        $optionValueIds = [];

        foreach ($options as $optionPosition => $valuesByName) {
            $optionName = (string) array_key_first($valuesByName);
            $option = ProductOption::withoutGlobalScopes()->create([
                'product_id' => $product->getKey(),
                'name' => $optionName,
                'position' => $optionPosition,
            ]);

            foreach (array_values($valuesByName[$optionName]) as $valuePosition => $value) {
                $optionValue = ProductOptionValue::withoutGlobalScopes()->create([
                    'product_option_id' => $option->getKey(),
                    'value' => $value,
                    'position' => $valuePosition,
                ]);

                $optionValueIds[$optionName][$value] = $optionValue->getKey();
            }
        }

        return $optionValueIds;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<string, int>>  $optionValueIds
     */
    private function createVariants(Store $store, Product $product, array $data, array $optionValueIds): void
    {
        $variants = $data['variants'] ?? $this->variantDefinitions($data);

        foreach ($variants as $position => $variantData) {
            $variant = ProductVariant::withoutGlobalScopes()->create([
                'product_id' => $product->getKey(),
                'sku' => $variantData['sku'] ?? $this->sku($data['sku_prefix'] ?? Str::upper(Str::slug($data['handle'], '-')), $variantData['options'] ?? []),
                'barcode' => $variantData['barcode'] ?? null,
                'price_amount' => $variantData['price_amount'],
                'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                'currency' => $store->default_currency,
                'weight_g' => $variantData['weight_g'] ?? 250,
                'requires_shipping' => $variantData['requires_shipping'] ?? true,
                'is_default' => $position === 0,
                'position' => $position,
                'status' => $variantData['status'] ?? 'active',
            ]);

            InventoryItem::withoutGlobalScopes()->updateOrCreate(
                ['variant_id' => $variant->getKey()],
                [
                    'store_id' => $store->getKey(),
                    'quantity_on_hand' => $variantData['quantity_on_hand'] ?? 0,
                    'quantity_reserved' => 0,
                    'policy' => $variantData['policy'] ?? 'deny',
                ],
            );

            $selectedOptions = $variantData['options'] ?? [];

            if ($selectedOptions !== []) {
                $variant->optionValues()->sync($this->selectedOptionValueIds($selectedOptions, $optionValueIds));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function variantDefinitions(array $data): array
    {
        $options = $data['options'] ?? [];
        $defaults = $data['variant_defaults'];

        if ($options === []) {
            return [[
                ...$defaults,
                'options' => [],
                'sku' => $data['sku'] ?? $this->sku($data['sku_prefix'] ?? Str::upper(Str::slug($data['handle'], '-')), []),
            ]];
        }

        return collect($this->optionCombinations($options))
            ->map(fn (array $selection): array => [
                ...$defaults,
                'options' => $selection,
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, array<int, string>>>  $options
     * @return array<int, array<string, string>>
     */
    private function optionCombinations(array $options): array
    {
        $combinations = [[]];

        foreach ($options as $valuesByName) {
            $optionName = (string) array_key_first($valuesByName);
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($valuesByName[$optionName] as $value) {
                    $next[] = [
                        ...$combination,
                        $optionName => $value,
                    ];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * @param  array<string, string>  $selectedOptions
     * @param  array<string, array<string, int>>  $optionValueIds
     * @return array<int, int>
     */
    private function selectedOptionValueIds(array $selectedOptions, array $optionValueIds): array
    {
        return collect($selectedOptions)
            ->map(fn (string $value, string $optionName): int => $optionValueIds[$optionName][$value])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $selectedOptions
     */
    private function sku(string $prefix, array $selectedOptions): string
    {
        $parts = collect($selectedOptions)
            ->values()
            ->map(fn (string $value): string => $this->skuToken($value))
            ->all();

        return collect([$prefix, ...$parts])
            ->filter()
            ->implode('-');
    }

    private function skuToken(string $value): string
    {
        return [
            'White' => 'WHT',
            'Black' => 'BLK',
            'Navy' => 'NVY',
            'Blue' => 'BLU',
            'Brown' => 'BRN',
            'Olive' => 'OLV',
            'Sky Blue' => 'SKY',
            'Beige' => 'BGE',
            'Khaki' => 'KHK',
            'Sand' => 'SND',
            'Burgundy' => 'BUR',
            'Natural' => 'NAT',
            'Grey' => 'GRY',
            'Camel' => 'CAM',
            'Charcoal' => 'CHA',
            'Silver' => 'SLV',
            'Red' => 'RED',
            '25 EUR' => '25',
            '50 EUR' => '50',
            '100 EUR' => '100',
            '256GB' => '256',
            '512GB' => '512',
            '1TB' => '1TB',
            'S/M' => 'SM',
            'L/XL' => 'LXL',
        ][$value] ?? Str::upper(Str::slug($value, ''));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fashionProducts(): array
    {
        return [
            $this->product('Classic Cotton T-Shirt', 'classic-cotton-t-shirt', 'Acme Basics', 'T-Shirts', ['new', 'popular'], ['new-arrivals', 't-shirts'], '<p>A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.</p>', [['Size' => ['S', 'M', 'L', 'XL']], ['Color' => ['White', 'Black', 'Navy']]], ['price_amount' => 2499, 'weight_g' => 200, 'quantity_on_hand' => 15], 'ACME-CTSH'),
            $this->product('Premium Slim Fit Jeans', 'premium-slim-fit-jeans', 'Acme Denim', 'Pants', ['new', 'sale'], ['new-arrivals', 'pants-jeans', 'sale'], '<p>Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.</p>', [['Size' => ['28', '30', '32', '34', '36']], ['Color' => ['Blue', 'Black']]], ['price_amount' => 7999, 'compare_at_amount' => 9999, 'weight_g' => 800, 'quantity_on_hand' => 8], 'ACME-JNS'),
            $this->product('Organic Hoodie', 'organic-hoodie', 'Acme Basics', 'Hoodies', ['new', 'trending'], ['new-arrivals'], '<p>Made from 100% organic cotton. Warm, soft, and sustainably produced.</p>', [['Size' => ['S', 'M', 'L', 'XL']]], ['price_amount' => 5999, 'weight_g' => 500, 'quantity_on_hand' => 20], 'ACME-HOOD'),
            $this->product('Leather Belt', 'leather-belt', 'Acme Accessories', 'Accessories', ['popular'], [], '<p>Genuine leather belt with brushed metal buckle. A wardrobe essential.</p>', [['Size' => ['S/M', 'L/XL']], ['Color' => ['Brown', 'Black']]], ['price_amount' => 3499, 'weight_g' => 150, 'quantity_on_hand' => 25], 'ACME-BELT'),
            $this->product('Running Sneakers', 'running-sneakers', 'Acme Sport', 'Shoes', ['trending'], ['new-arrivals'], '<p>Lightweight running sneakers with responsive cushioning and breathable mesh upper.</p>', [['Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44']], ['Color' => ['White', 'Black']]], ['price_amount' => 11999, 'weight_g' => 600, 'quantity_on_hand' => 5], 'ACME-RUN'),
            $this->product('Graphic Print Tee', 'graphic-print-tee', 'Acme Basics', 'T-Shirts', ['new'], ['t-shirts'], '<p>Bold graphic print on soft cotton. Express yourself with this statement piece.</p>', [['Size' => ['S', 'M', 'L', 'XL']]], ['price_amount' => 2999, 'weight_g' => 210, 'quantity_on_hand' => 18], 'ACME-GTEE'),
            $this->product('V-Neck Linen Tee', 'v-neck-linen-tee', 'Acme Basics', 'T-Shirts', ['popular'], ['t-shirts'], '<p>Lightweight linen blend v-neck. Perfect for warm summer days.</p>', [['Size' => ['S', 'M', 'L']], ['Color' => ['Beige', 'Olive', 'Sky Blue']]], ['price_amount' => 3499, 'weight_g' => 180, 'quantity_on_hand' => 12], 'ACME-VNECK'),
            $this->product('Striped Polo Shirt', 'striped-polo-shirt', 'Acme Basics', 'T-Shirts', ['sale'], ['t-shirts', 'sale'], '<p>Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.</p>', [['Size' => ['S', 'M', 'L', 'XL']]], ['price_amount' => 2799, 'compare_at_amount' => 3999, 'weight_g' => 250, 'quantity_on_hand' => 10], 'ACME-POLO'),
            $this->product('Cargo Pants', 'cargo-pants', 'Acme Workwear', 'Pants', ['popular'], ['pants-jeans'], '<p>Utility cargo pants with multiple pockets. Durable cotton twill construction.</p>', [['Size' => ['30', '32', '34', '36']], ['Color' => ['Khaki', 'Olive', 'Black']]], ['price_amount' => 5499, 'weight_g' => 700, 'quantity_on_hand' => 14], 'ACME-CARGO'),
            $this->product('Chino Shorts', 'chino-shorts', 'Acme Basics', 'Pants', ['new', 'trending'], ['pants-jeans', 'new-arrivals'], '<p>Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.</p>', [['Size' => ['30', '32', '34', '36']], ['Color' => ['Navy', 'Sand']]], ['price_amount' => 3999, 'weight_g' => 350, 'quantity_on_hand' => 16], 'ACME-CHINO'),
            $this->product('Wide Leg Trousers', 'wide-leg-trousers', 'Acme Denim', 'Pants', ['sale'], ['pants-jeans', 'sale'], '<p>Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.</p>', [['Size' => ['S', 'M', 'L']]], ['price_amount' => 4999, 'compare_at_amount' => 6999, 'weight_g' => 550, 'quantity_on_hand' => 7], 'ACME-WIDE'),
            $this->product('Wool Scarf', 'wool-scarf', 'Acme Accessories', 'Accessories', ['popular'], [], '<p>Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.</p>', [['Color' => ['Grey', 'Burgundy', 'Navy']]], ['price_amount' => 2999, 'weight_g' => 120, 'quantity_on_hand' => 30], 'ACME-SCARF'),
            $this->product('Canvas Tote Bag', 'canvas-tote-bag', 'Acme Accessories', 'Accessories', ['trending'], [], '<p>Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.</p>', [['Color' => ['Natural', 'Black']]], ['price_amount' => 1999, 'weight_g' => 300, 'quantity_on_hand' => 40], 'ACME-TOTE'),
            $this->product('Bucket Hat', 'bucket-hat', 'Acme Accessories', 'Accessories', ['new', 'trending'], ['new-arrivals'], '<p>Lightweight bucket hat for sun protection. Packable design, washed cotton twill.</p>', [['Size' => ['S/M', 'L/XL']], ['Color' => ['Beige', 'Black', 'Olive']]], ['price_amount' => 2499, 'weight_g' => 80, 'quantity_on_hand' => 22], 'ACME-HAT'),
            $this->product('Unreleased Winter Jacket', 'unreleased-winter-jacket', 'Acme Outerwear', 'Jackets', ['limited'], [], '<p>Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.</p>', [['Size' => ['S', 'M', 'L', 'XL']]], ['price_amount' => 14999, 'weight_g' => 900, 'quantity_on_hand' => 0], 'ACME-WINTER', 'draft', null),
            $this->product('Discontinued Raincoat', 'discontinued-raincoat', 'Acme Outerwear', 'Jackets', [], [], '<p>Lightweight waterproof raincoat. This product has been discontinued.</p>', [['Size' => ['M', 'L']]], ['price_amount' => 8999, 'weight_g' => 400, 'quantity_on_hand' => 3], 'ACME-RAIN', 'archived', now()->subMonths(6)),
            $this->product('Limited Edition Sneakers', 'limited-edition-sneakers', 'Acme Sport', 'Shoes', ['limited'], [], '<p>Limited edition collaboration sneakers. Once they are gone, they are gone.</p>', [['Size' => ['EU 40', 'EU 42', 'EU 44']]], ['price_amount' => 15999, 'weight_g' => 650, 'quantity_on_hand' => 0, 'policy' => 'deny'], 'ACME-LIMITED'),
            $this->product('Backorder Denim Jacket', 'backorder-denim-jacket', 'Acme Denim', 'Jackets', ['popular'], [], '<p>Classic denim jacket. Currently on backorder - ships within 2-3 weeks.</p>', [['Size' => ['S', 'M', 'L', 'XL']]], ['price_amount' => 9999, 'weight_g' => 750, 'quantity_on_hand' => 0, 'policy' => 'continue'], 'ACME-BACKORDER'),
            $this->product('Gift Card', 'gift-card', 'Acme Fashion', 'Gift Cards', ['popular'], [], '<p>Digital gift card delivered via email. The perfect gift when you are not sure what to choose.</p>', [['Amount' => ['25 EUR', '50 EUR', '100 EUR']]], ['price_amount' => 2500, 'weight_g' => 0, 'requires_shipping' => false, 'quantity_on_hand' => 9999], 'ACME-GIFT', variants: [
                ['options' => ['Amount' => '25 EUR'], 'sku' => 'ACME-GIFT-25', 'price_amount' => 2500, 'weight_g' => 0, 'requires_shipping' => false, 'quantity_on_hand' => 9999],
                ['options' => ['Amount' => '50 EUR'], 'sku' => 'ACME-GIFT-50', 'price_amount' => 5000, 'weight_g' => 0, 'requires_shipping' => false, 'quantity_on_hand' => 9999],
                ['options' => ['Amount' => '100 EUR'], 'sku' => 'ACME-GIFT-100', 'price_amount' => 10000, 'weight_g' => 0, 'requires_shipping' => false, 'quantity_on_hand' => 9999],
            ]),
            $this->product('Cashmere Overcoat', 'cashmere-overcoat', 'Acme Premium', 'Jackets', ['limited', 'new'], ['new-arrivals'], '<p>Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.</p>', [['Size' => ['S', 'M', 'L']], ['Color' => ['Camel', 'Charcoal']]], ['price_amount' => 49999, 'weight_g' => 1200, 'quantity_on_hand' => 3], 'ACME-COAT'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function electronicsProducts(): array
    {
        return [
            $this->product('Pro Laptop 15', 'pro-laptop-15', 'TechCorp', 'Laptops', ['featured'], ['featured'], '<p>Performance laptop for tenant isolation tests.</p>', [['Storage' => ['256GB', '512GB', '1TB']]], ['price_amount' => 99999, 'weight_g' => 1800, 'quantity_on_hand' => 10], 'TECH-LAPTOP', variants: [
                ['options' => ['Storage' => '256GB'], 'sku' => 'TECH-LAPTOP-256', 'price_amount' => 99999, 'weight_g' => 1800, 'quantity_on_hand' => 10],
                ['options' => ['Storage' => '512GB'], 'sku' => 'TECH-LAPTOP-512', 'price_amount' => 119999, 'weight_g' => 1800, 'quantity_on_hand' => 10],
                ['options' => ['Storage' => '1TB'], 'sku' => 'TECH-LAPTOP-1TB', 'price_amount' => 149999, 'weight_g' => 1800, 'quantity_on_hand' => 10],
            ]),
            $this->product('Wireless Headphones', 'wireless-headphones', 'AudioMax', 'Audio', ['featured'], ['featured'], '<p>Wireless headphones for tenant isolation tests.</p>', [['Color' => ['Black', 'Silver']]], ['price_amount' => 14999, 'weight_g' => 250, 'quantity_on_hand' => 25], 'TECH-HEADPHONES'),
            $this->product('USB-C Cable 2m', 'usb-c-cable-2m', 'CablePro', 'Cables', ['accessories'], ['accessories'], '<p>Two meter USB-C cable.</p>', [], ['price_amount' => 1299, 'weight_g' => 50, 'quantity_on_hand' => 200], 'TECH-CABLE'),
            $this->product('Mechanical Keyboard', 'mechanical-keyboard', 'KeyTech', 'Peripherals', ['featured'], ['featured'], '<p>Mechanical keyboard with selectable switch types.</p>', [['Switch Type' => ['Red', 'Blue', 'Brown']]], ['price_amount' => 12999, 'weight_g' => 1100, 'quantity_on_hand' => 15], 'TECH-KEYBOARD'),
            $this->product('Monitor Stand', 'monitor-stand', 'DeskGear', 'Accessories', ['accessories'], ['accessories'], '<p>Desktop monitor stand.</p>', [], ['price_amount' => 4999, 'weight_g' => 2500, 'quantity_on_hand' => 30], 'TECH-STAND'),
        ];
    }

    /**
     * @param  array<int, array<string, array<int, string>>>  $options
     * @param  array<string, mixed>  $variantDefaults
     * @param  array<int, array<string, mixed>>|null  $variants
     * @return array<string, mixed>
     */
    private function product(
        string $title,
        string $handle,
        string $vendor,
        string $productType,
        array $tags,
        array $collections,
        string $descriptionHtml,
        array $options,
        array $variantDefaults,
        string $skuPrefix,
        string $status = 'active',
        mixed $publishedAt = null,
        ?array $variants = null,
    ): array {
        return [
            'title' => $title,
            'handle' => $handle,
            'status' => $status,
            'vendor' => $vendor,
            'product_type' => $productType,
            'tags' => $tags,
            'collections' => $collections,
            'description_html' => $descriptionHtml,
            'published_at' => $status === 'draft' ? null : ($publishedAt ?? now()),
            'options' => $options,
            'variant_defaults' => [
                'compare_at_amount' => null,
                'requires_shipping' => true,
                'policy' => 'deny',
                ...$variantDefaults,
            ],
            'sku_prefix' => $skuPrefix,
            'variants' => $variants,
        ];
    }
}
