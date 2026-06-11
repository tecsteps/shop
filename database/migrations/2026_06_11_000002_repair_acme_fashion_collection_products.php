<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Repair stale preview/demo databases where the Acme Fashion collections
     * exist but their seeded product pivot rows are missing.
     */
    public function up(): void
    {
        $store = DB::table('stores')->where('handle', 'acme-fashion')->first(['id']);

        if ($store === null) {
            return;
        }

        $storeId = (int) $store->id;

        foreach ($this->collectionProducts() as $collectionHandle => $productHandles) {
            $collectionId = DB::table('collections')
                ->where('store_id', $storeId)
                ->where('handle', $collectionHandle)
                ->value('id');

            if ($collectionId === null) {
                continue;
            }

            foreach ($productHandles as $position => $productHandle) {
                $productId = DB::table('products')
                    ->where('store_id', $storeId)
                    ->where('handle', $productHandle)
                    ->value('id');

                if ($productId === null) {
                    continue;
                }

                DB::table('collection_products')->updateOrInsert(
                    [
                        'collection_id' => $collectionId,
                        'product_id' => $productId,
                    ],
                    ['position' => $position],
                );
            }
        }
    }

    /**
     * Reverse is intentionally empty: this repairs demo seed drift and should
     * not remove user-visible collection assignments.
     */
    public function down(): void
    {
        //
    }

    /**
     * @return array<string, list<string>>
     */
    private function collectionProducts(): array
    {
        return [
            'new-arrivals' => [
                'classic-cotton-t-shirt',
                'premium-slim-fit-jeans',
                'organic-hoodie',
                'running-sneakers',
                'chino-shorts',
                'bucket-hat',
                'cashmere-overcoat',
            ],
            't-shirts' => [
                'classic-cotton-t-shirt',
                'graphic-print-tee',
                'v-neck-linen-tee',
                'striped-polo-shirt',
            ],
            'pants-jeans' => [
                'premium-slim-fit-jeans',
                'cargo-pants',
                'chino-shorts',
                'wide-leg-trousers',
            ],
            'sale' => [
                'premium-slim-fit-jeans',
                'striped-polo-shirt',
                'wide-leg-trousers',
            ],
        ];
    }
};
