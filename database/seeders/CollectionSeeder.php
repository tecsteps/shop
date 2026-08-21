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
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get()->keyBy('handle');

        foreach ([
            ['store' => 'acme-fashion', 'title' => 'New Arrivals', 'handle' => 'new-arrivals', 'description' => 'Discover the latest additions to our store.'],
            ['store' => 'acme-fashion', 'title' => 'T-Shirts', 'handle' => 't-shirts', 'description' => 'Premium cotton tees for every occasion.'],
            ['store' => 'acme-fashion', 'title' => 'Pants & Jeans', 'handle' => 'pants-jeans', 'description' => 'Find the perfect fit from our denim and trouser range.'],
            ['store' => 'acme-fashion', 'title' => 'Sale', 'handle' => 'sale', 'description' => 'Great deals on selected items.'],
            ['store' => 'acme-electronics', 'title' => 'Featured', 'handle' => 'featured', 'description' => 'Our most popular technology.'],
            ['store' => 'acme-electronics', 'title' => 'Accessories', 'handle' => 'accessories', 'description' => 'The extras that complete your setup.'],
        ] as $collection) {
            Collection::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $stores[$collection['store']]->getKey(), 'handle' => $collection['handle']],
                ['title' => $collection['title'], 'description' => '<p>'.$collection['description'].'</p>', 'status' => 'active', 'image_url' => null],
            );
        }
    }
}
