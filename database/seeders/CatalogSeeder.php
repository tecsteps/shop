<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /** @var list<array{title: string, handle: string, vendor: string, type: string, variants: list<array{sku: string, price: int}>}> */
    private array $products = [
        [
            'title' => 'Classic Tee',
            'handle' => 'classic-tee',
            'vendor' => 'Demo Brand',
            'type' => 'Apparel',
            'variants' => [
                ['sku' => 'TEE-S', 'price' => 1999],
                ['sku' => 'TEE-M', 'price' => 1999],
                ['sku' => 'TEE-L', 'price' => 1999],
            ],
        ],
        [
            'title' => 'Hoodie',
            'handle' => 'hoodie',
            'vendor' => 'Demo Brand',
            'type' => 'Apparel',
            'variants' => [
                ['sku' => 'HOOD-M', 'price' => 4999],
                ['sku' => 'HOOD-L', 'price' => 4999],
            ],
        ],
        [
            'title' => 'Cap',
            'handle' => 'cap',
            'vendor' => 'Demo Brand',
            'type' => 'Accessories',
            'variants' => [
                ['sku' => 'CAP-001', 'price' => 2499],
            ],
        ],
        [
            'title' => 'Tote Bag',
            'handle' => 'tote-bag',
            'vendor' => 'Demo Brand',
            'type' => 'Accessories',
            'variants' => [
                ['sku' => 'TOTE-001', 'price' => 1499],
            ],
        ],
        [
            'title' => 'Sneakers',
            'handle' => 'sneakers',
            'vendor' => 'Demo Brand',
            'type' => 'Footwear',
            'variants' => [
                ['sku' => 'SNK-42', 'price' => 7999],
                ['sku' => 'SNK-43', 'price' => 7999],
                ['sku' => 'SNK-44', 'price' => 7999],
            ],
        ],
        [
            'title' => 'Mug',
            'handle' => 'mug',
            'vendor' => 'Demo Brand',
            'type' => 'Home',
            'variants' => [
                ['sku' => 'MUG-001', 'price' => 999],
            ],
        ],
    ];

    public function run(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $createdProducts = [];

        foreach ($this->products as $data) {
            $product = Product::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('handle', $data['handle'])
                ->first();

            if ($product === null) {
                $product = Product::create([
                    'store_id' => $store->id,
                    'title' => $data['title'],
                    'handle' => $data['handle'],
                    'status' => 'active',
                    'description_html' => '<p>'.$data['title'].' description.</p>',
                    'vendor' => $data['vendor'],
                    'product_type' => $data['type'],
                    'tags' => [$data['type'], $data['vendor']],
                    'published_at' => now(),
                ]);

                foreach ($data['variants'] as $index => $variantData) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantData['sku'],
                        'price_amount' => $variantData['price'],
                        'currency' => 'EUR',
                        'weight_g' => 250,
                        'requires_shipping' => true,
                        'is_default' => $index === 0,
                        'position' => $index,
                        'status' => 'active',
                    ]);

                    InventoryItem::create([
                        'store_id' => $store->id,
                        'variant_id' => $variant->id,
                        'quantity_on_hand' => 50,
                        'quantity_reserved' => 0,
                        'policy' => 'deny',
                    ]);
                }
            }

            $createdProducts[] = $product;
        }

        $featured = Collection::query()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'featured'],
            [
                'title' => 'Featured',
                'type' => 'manual',
                'status' => 'active',
            ]
        );

        $sale = Collection::query()->firstOrCreate(
            ['store_id' => $store->id, 'handle' => 'sale'],
            [
                'title' => 'Sale',
                'type' => 'manual',
                'status' => 'active',
            ]
        );

        $featuredIds = array_slice(array_map(fn (Product $p): int => $p->id, $createdProducts), 0, 4);
        $saleIds = array_slice(array_map(fn (Product $p): int => $p->id, $createdProducts), 2, 4);

        $featured->products()->syncWithoutDetaching(array_combine(
            $featuredIds,
            array_map(fn (int $position): array => ['position' => $position], array_keys($featuredIds))
        ));

        $sale->products()->syncWithoutDetaching(array_combine(
            $saleIds,
            array_map(fn (int $position): array => ['position' => $position], array_keys($saleIds))
        ));
    }
}
