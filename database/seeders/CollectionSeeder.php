<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

        app()->instance('current_store', $fashion);

        $fashionCollections = [
            ['title' => 'New Arrivals', 'handle' => 'new-arrivals', 'description_html' => '<p>Discover the latest additions to our store.</p>'],
            ['title' => 'T-Shirts', 'handle' => 't-shirts', 'description_html' => '<p>Premium cotton tees for every occasion.</p>'],
            ['title' => 'Pants & Jeans', 'handle' => 'pants-jeans', 'description_html' => '<p>Find the perfect fit from our denim and trouser range.</p>'],
            ['title' => 'Sale', 'handle' => 'sale', 'description_html' => '<p>Great deals on selected items.</p>'],
        ];

        foreach ($fashionCollections as $data) {
            Collection::create(array_merge($data, [
                'store_id' => $fashion->id,
                'type' => 'manual',
                'status' => 'active',
            ]));
        }

        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        app()->instance('current_store', $electronics);

        $electronicsCollections = [
            ['title' => 'Featured', 'handle' => 'featured', 'description_html' => '<p>Our featured products.</p>'],
            ['title' => 'Accessories', 'handle' => 'accessories', 'description_html' => '<p>Essential accessories.</p>'],
        ];

        foreach ($electronicsCollections as $data) {
            Collection::create(array_merge($data, [
                'store_id' => $electronics->id,
                'type' => 'manual',
                'status' => 'active',
            ]));
        }
    }
}
