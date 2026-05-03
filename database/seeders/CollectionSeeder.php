<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $collectionsByStore = [
            'acme-fashion' => [
                ['title' => 'New Arrivals', 'handle' => 'new-arrivals', 'description_html' => '<p>Discover the latest additions to our store.</p>'],
                ['title' => 'T-Shirts', 'handle' => 't-shirts', 'description_html' => '<p>Premium cotton tees for every occasion.</p>'],
                ['title' => 'Pants & Jeans', 'handle' => 'pants-jeans', 'description_html' => '<p>Find the perfect fit from our denim and trouser range.</p>'],
                ['title' => 'Sale', 'handle' => 'sale', 'description_html' => '<p>Great deals on selected items.</p>'],
            ],
            'acme-electronics' => [
                ['title' => 'Featured', 'handle' => 'featured', 'description_html' => '<p>Featured electronics for tenant isolation tests.</p>'],
                ['title' => 'Accessories', 'handle' => 'accessories', 'description_html' => '<p>Electronics accessories.</p>'],
            ],
        ];

        foreach ($collectionsByStore as $storeHandle => $collections) {
            $store = Store::query()->where('handle', $storeHandle)->firstOrFail();

            foreach ($collections as $collection) {
                Collection::query()->updateOrCreate(
                    [
                        'store_id' => $store->getKey(),
                        'handle' => $collection['handle'],
                    ],
                    [
                        'title' => $collection['title'],
                        'description_html' => $collection['description_html'],
                        'type' => 'manual',
                        'status' => 'active',
                    ],
                );
            }
        }
    }
}
