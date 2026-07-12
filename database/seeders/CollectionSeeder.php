<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $sets = [
            'acme-fashion' => [
                ['New Arrivals', 'new-arrivals', 'Discover the latest additions to our store.'],
                ['T-Shirts', 't-shirts', 'Premium cotton tees for every occasion.'],
                ['Pants & Jeans', 'pants-jeans', 'Find the perfect fit from our denim and trouser range.'],
                ['Sale', 'sale', 'Great deals on selected items.'],
            ],
            'acme-electronics' => [['Featured', 'featured', 'Premium technology for work and play.'], ['Accessories', 'accessories', 'Essential accessories for every setup.']],
        ];
        foreach ($sets as $handle => $collections) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();
            foreach ($collections as [$title, $collectionHandle, $description]) {
                Collection::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'handle' => $collectionHandle], [
                    'title' => $title, 'description_html' => "<p>{$description}</p>", 'type' => 'manual', 'status' => 'active',
                ]);
            }
        }
    }
}
