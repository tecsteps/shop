<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\VariantMatrixService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $collection = Collection::query()
            ->where('store_id', $store->id)
            ->where('handle', 'summer-essentials')
            ->firstOrFail();

        $shirt = Product::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'linen-shirt',
            ],
            [
                'title' => 'Linen Shirt',
                'status' => ProductStatus::Active,
                'description_html' => '<p>A breathable linen shirt with a relaxed fit.</p>',
                'vendor' => 'Acme Apparel',
                'product_type' => 'Shirts',
                'tags' => ['new', 'summer'],
                'published_at' => now(),
            ],
        );

        $variant = ProductVariant::query()->updateOrCreate(
            [
                'product_id' => $shirt->id,
                'sku' => 'LINEN-SHIRT-DEFAULT',
            ],
            [
                'price_amount' => 4999,
                'currency' => $store->default_currency,
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ],
        );

        $variant->inventoryItem()->withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $variant->id],
            [
                'store_id' => $store->id,
                'quantity_on_hand' => 50,
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ],
        );

        $tee = Product::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'logo-tee',
            ],
            [
                'title' => 'Logo Tee',
                'status' => ProductStatus::Active,
                'description_html' => '<p>Soft cotton tee with the Acme mark.</p>',
                'vendor' => 'Acme Apparel',
                'product_type' => 'Shirts',
                'tags' => ['popular'],
                'published_at' => now(),
            ],
        );

        $sizeOption = ProductOption::query()->updateOrCreate(
            [
                'product_id' => $tee->id,
                'position' => 0,
            ],
            ['name' => 'Size'],
        );

        foreach (['S', 'M', 'L'] as $position => $value) {
            $sizeOption->values()->updateOrCreate(
                ['position' => $position],
                ['value' => $value],
            );
        }

        ProductVariant::query()->firstOrCreate(
            [
                'product_id' => $tee->id,
                'sku' => 'LOGO-TEE-TEMPLATE',
            ],
            [
                'price_amount' => 2999,
                'currency' => $store->default_currency,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ],
        );

        app(VariantMatrixService::class)->rebuildMatrix($tee->refresh());

        $tee->variants()->with('inventoryItem')->get()->each(function (ProductVariant $variant) use ($store): void {
            $variant->inventoryItem()->withoutGlobalScopes()->updateOrCreate(
                ['variant_id' => $variant->id],
                [
                    'store_id' => $store->id,
                    'quantity_on_hand' => 30,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ],
            );
        });

        DB::table('collection_products')->updateOrInsert(
            [
                'collection_id' => $collection->id,
                'product_id' => $shirt->id,
            ],
            ['position' => 0],
        );

        DB::table('collection_products')->updateOrInsert(
            [
                'collection_id' => $collection->id,
                'product_id' => $tee->id,
            ],
            ['position' => 1],
        );

        $this->seedFashionCatalog($store);
        $this->seedElectronicsCatalog();
    }

    private function seedFashionCatalog(Store $store): void
    {
        $this->seedClassicCottonTShirt($store);

        $products = [
            ['premium-slim-fit-jeans', 'Premium Slim Fit Jeans', 7999, 10999, 'Acme Denim', 'Jeans', ['denim', 'sale'], 'denim', 45, 'deny', ProductStatus::Active],
            ['merino-crew-sweater', 'Merino Crew Sweater', 8999, null, 'Acme Knitwear', 'Sweaters', ['winter', 'wool'], 'new-arrivals', 24, 'deny', ProductStatus::Active],
            ['organic-poplin-shirt', 'Organic Poplin Shirt', 5999, null, 'Acme Apparel', 'Shirts', ['organic', 'workwear'], 'new-arrivals', 35, 'deny', ProductStatus::Active],
            ['chino-trouser', 'Chino Trouser', 6999, null, 'Acme Apparel', 'Pants', ['cotton'], 'new-arrivals', 38, 'deny', ProductStatus::Active],
            ['canvas-tote-bag', 'Canvas Tote Bag', 2499, null, 'Acme Bags', 'Bags', ['canvas', 'accessories'], 'accessories', 80, 'deny', ProductStatus::Active],
            ['ribbed-socks-pack', 'Ribbed Socks Pack', 1499, null, 'Acme Basics', 'Accessories', ['basics'], 'accessories', 120, 'deny', ProductStatus::Active],
            ['field-jacket', 'Field Jacket', 12999, null, 'Acme Outerwear', 'Jackets', ['outerwear'], 'new-arrivals', 18, 'deny', ProductStatus::Active],
            ['pleated-midi-skirt', 'Pleated Midi Skirt', 7499, null, 'Acme Apparel', 'Skirts', ['summer'], 'summer-essentials', 22, 'deny', ProductStatus::Active],
            ['relaxed-cotton-shorts', 'Relaxed Cotton Shorts', 3999, null, 'Acme Apparel', 'Shorts', ['summer'], 'summer-essentials', 36, 'deny', ProductStatus::Active],
            ['structured-cap', 'Structured Cap', 1999, null, 'Acme Accessories', 'Accessories', ['cap'], 'accessories', 90, 'deny', ProductStatus::Active],
            ['wool-overshirt', 'Wool Overshirt', 11999, null, 'Acme Outerwear', 'Shirts', ['wool'], 'new-arrivals', 16, 'deny', ProductStatus::Active],
            ['everyday-tank-top', 'Everyday Tank Top', 2199, null, 'Acme Basics', 'T-Shirts', ['basics', 'summer'], 't-shirts', 64, 'deny', ProductStatus::Active],
            ['heavyweight-pocket-tee', 'Heavyweight Pocket Tee', 3499, null, 'Acme Basics', 'T-Shirts', ['tee', 'cotton'], 't-shirts', 52, 'deny', ProductStatus::Active],
            ['archive-sample-parka', 'Archive Sample Parka', 15999, null, 'Acme Archive', 'Jackets', ['archive'], 'new-arrivals', 5, 'deny', ProductStatus::Draft],
            ['suede-weekender-bag', 'Suede Weekender Bag', 14999, null, 'Acme Bags', 'Bags', ['travel'], 'accessories', 12, 'deny', ProductStatus::Active],
            ['sold-out-canvas-sneaker', 'Sold Out Canvas Sneaker', 6499, null, 'Acme Footwear', 'Shoes', ['sold-out'], 'new-arrivals', 0, 'deny', ProductStatus::Active],
            ['backorder-utility-vest', 'Backorder Utility Vest', 8499, null, 'Acme Outerwear', 'Vests', ['backorder'], 'new-arrivals', 0, 'continue', ProductStatus::Active],
        ];

        foreach ($products as [$handle, $title, $price, $compareAt, $vendor, $type, $tags, $collection, $quantity, $policy, $status]) {
            $product = $this->seedSimpleProduct($store, [
                'handle' => $handle,
                'title' => $title,
                'price' => $price,
                'compare_at' => $compareAt,
                'vendor' => $vendor,
                'product_type' => $type,
                'tags' => $tags,
                'quantity' => $quantity,
                'policy' => $policy,
                'status' => $status,
                'sku' => strtoupper(str_replace('-', '-', $handle)).'-DEFAULT',
            ]);

            $this->attachProductToCollection($store, $product, $collection);
        }
    }

    private function seedElectronicsCatalog(): void
    {
        $store = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ([
            ['usb-c-dock', 'USB-C Dock', 8999, 'Acme Devices', 'Docks', 'desk-setup', 20],
            ['monitor-stand', 'Monitor Stand', 4999, 'Acme Workspace', 'Stands', 'desk-setup', 30],
            ['wireless-keyboard', 'Wireless Keyboard', 7999, 'Acme Devices', 'Keyboards', 'desk-setup', 25],
            ['noise-cancelling-headphones', 'Noise Cancelling Headphones', 17999, 'Acme Audio', 'Headphones', 'audio', 15],
            ['portable-speaker', 'Portable Speaker', 9999, 'Acme Audio', 'Speakers', 'audio', 18],
        ] as [$handle, $title, $price, $vendor, $type, $collection, $quantity]) {
            $product = $this->seedSimpleProduct($store, [
                'handle' => $handle,
                'title' => $title,
                'price' => $price,
                'compare_at' => null,
                'vendor' => $vendor,
                'product_type' => $type,
                'tags' => ['electronics'],
                'quantity' => $quantity,
                'policy' => 'deny',
                'status' => ProductStatus::Active,
                'sku' => strtoupper(str_replace('-', '-', $handle)).'-DEFAULT',
            ]);

            $this->attachProductToCollection($store, $product, $collection);
        }
    }

    /**
     * @param  array{handle: string, title: string, price: int, compare_at: ?int, vendor: string, product_type: string, tags: list<string>, quantity: int, policy: string, status: ProductStatus, sku: string}  $data
     */
    private function seedSimpleProduct(Store $store, array $data): Product
    {
        $product = Product::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => $data['handle'],
            ],
            [
                'title' => $data['title'],
                'status' => $data['status'],
                'description_html' => '<p>'.$data['title'].' from '.$data['vendor'].'.</p>',
                'vendor' => $data['vendor'],
                'product_type' => $data['product_type'],
                'tags' => $data['tags'],
                'published_at' => $data['status'] === ProductStatus::Active ? now()->subDays(3) : null,
            ],
        );

        $variant = ProductVariant::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'sku' => $data['sku'],
            ],
            [
                'price_amount' => $data['price'],
                'compare_at_amount' => $data['compare_at'],
                'currency' => $store->default_currency,
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ],
        );

        $variant->inventoryItem()->withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $variant->id],
            [
                'store_id' => $store->id,
                'quantity_on_hand' => $data['quantity'],
                'quantity_reserved' => 0,
                'policy' => $data['policy'],
            ],
        );

        return $product->refresh();
    }

    private function seedClassicCottonTShirt(Store $store): void
    {
        $product = Product::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'classic-cotton-t-shirt',
            ],
            [
                'title' => 'Classic Cotton T-Shirt',
                'status' => ProductStatus::Active,
                'description_html' => '<p>A classic cotton t-shirt with size and color variants.</p>',
                'vendor' => 'Acme Basics',
                'product_type' => 'T-Shirts',
                'tags' => ['organic', 'cotton'],
                'published_at' => now()->subDays(2),
            ],
        );

        foreach ([['Size', ['S', 'M', 'L', 'XL']], ['Color', ['Black', 'White', 'Navy']]] as $optionIndex => [$name, $values]) {
            $option = ProductOption::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'position' => $optionIndex,
                ],
                ['name' => $name],
            );

            foreach ($values as $valueIndex => $value) {
                $option->values()->updateOrCreate(
                    ['position' => $valueIndex],
                    ['value' => $value],
                );
            }
        }

        ProductVariant::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'sku' => 'CLASSIC-COTTON-TEMPLATE',
            ],
            [
                'price_amount' => 2499,
                'currency' => $store->default_currency,
                'is_default' => true,
                'position' => 0,
                'status' => VariantStatus::Active,
            ],
        );

        app(VariantMatrixService::class)->rebuildMatrix($product->refresh());

        $product->variants()
            ->with('optionValues')
            ->orderBy('position')
            ->get()
            ->values()
            ->each(function (ProductVariant $variant, int $index) use ($store): void {
                $optionSku = $variant->optionValues
                    ->sortBy('product_option_id')
                    ->pluck('value')
                    ->map(fn (string $value): string => strtoupper($value))
                    ->join('-');

                $variant->forceFill([
                    'sku' => 'CLASSIC-COTTON-'.($optionSku ?: 'DEFAULT'),
                    'price_amount' => 2499,
                    'currency' => $store->default_currency,
                    'is_default' => $index === 0,
                    'status' => VariantStatus::Active,
                ])->save();

                $variant->inventoryItem()->withoutGlobalScopes()->updateOrCreate(
                    ['variant_id' => $variant->id],
                    [
                        'store_id' => $store->id,
                        'quantity_on_hand' => 25,
                        'quantity_reserved' => 0,
                        'policy' => 'deny',
                    ],
                );
            });

        $this->attachProductToCollection($store, $product, 't-shirts');
        $this->attachProductToCollection($store, $product, 'new-arrivals');
    }

    private function attachProductToCollection(Store $store, Product $product, string $collectionHandle): void
    {
        $collection = Collection::query()
            ->where('store_id', $store->id)
            ->where('handle', $collectionHandle)
            ->first();

        if (! $collection) {
            return;
        }

        DB::table('collection_products')->updateOrInsert(
            [
                'collection_id' => $collection->id,
                'product_id' => $product->id,
            ],
            ['position' => $collection->products()->count()],
        );
    }
}
