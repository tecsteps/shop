<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        // Acme Fashion collections
        Collection::factory()->create([
            'store_id' => $fashion->id,
            'title' => 'New Arrivals',
            'handle' => 'new-arrivals',
            'type' => 'manual',
            'status' => 'active',
            'description_html' => '<p>Discover the latest additions to our store.</p>',
        ]);

        Collection::factory()->create([
            'store_id' => $fashion->id,
            'title' => 'T-Shirts',
            'handle' => 't-shirts',
            'type' => 'manual',
            'status' => 'active',
            'description_html' => '<p>Premium cotton tees for every occasion.</p>',
        ]);

        Collection::factory()->create([
            'store_id' => $fashion->id,
            'title' => 'Pants & Jeans',
            'handle' => 'pants-jeans',
            'type' => 'manual',
            'status' => 'active',
            'description_html' => '<p>Find the perfect fit from our denim and trouser range.</p>',
        ]);

        Collection::factory()->create([
            'store_id' => $fashion->id,
            'title' => 'Sale',
            'handle' => 'sale',
            'type' => 'manual',
            'status' => 'active',
            'description_html' => '<p>Great deals on selected items.</p>',
        ]);

        // Acme Electronics collections
        Collection::factory()->create([
            'store_id' => $electronics->id,
            'title' => 'Featured',
            'handle' => 'featured',
            'type' => 'manual',
            'status' => 'active',
            'description_html' => '<p>Our featured products.</p>',
        ]);

        Collection::factory()->create([
            'store_id' => $electronics->id,
            'title' => 'Accessories',
            'handle' => 'accessories',
            'type' => 'manual',
            'status' => 'active',
            'description_html' => '<p>Essential tech accessories.</p>',
        ]);
    }
}
