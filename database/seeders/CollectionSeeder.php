<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $collections = [
                'acme-fashion' => [
                    ['New Arrivals', 'new-arrivals', '<p>Discover the latest additions to our store.</p>'],
                    ['T-Shirts', 't-shirts', '<p>Premium cotton tees for every occasion.</p>'],
                    ['Pants & Jeans', 'pants-jeans', '<p>Find the perfect fit from our denim and trouser range.</p>'],
                    ['Sale', 'sale', '<p>Great deals on selected items.</p>'],
                ],
                'acme-electronics' => [
                    ['Featured', 'featured', '<p>Our featured technology products.</p>'],
                    ['Accessories', 'accessories', '<p>Essential accessories for your devices.</p>'],
                ],
            ];

            foreach ($collections as $handle => $storeCollections) {
                $store = Store::query()->where('handle', $handle)->firstOrFail();

                foreach ($storeCollections as [$title, $collectionHandle, $description]) {
                    Collection::query()->updateOrCreate(
                        ['store_id' => $store->id, 'handle' => $collectionHandle],
                        [
                            'title' => $title,
                            'description_html' => $description,
                            'type' => 'manual',
                            'status' => 'active',
                        ],
                    );
                }
            }
        });
    }
}
