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
        DB::transaction(function () {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            // Acme Fashion collections
            $fashionCollections = [
                [
                    'title' => 'New Arrivals',
                    'handle' => 'new-arrivals',
                    'description_html' => '<p>Discover the latest additions to our store.</p>',
                ],
                [
                    'title' => 'T-Shirts',
                    'handle' => 't-shirts',
                    'description_html' => '<p>Premium cotton tees for every occasion.</p>',
                ],
                [
                    'title' => 'Pants & Jeans',
                    'handle' => 'pants-jeans',
                    'description_html' => '<p>Find the perfect fit from our denim and trouser range.</p>',
                ],
                [
                    'title' => 'Sale',
                    'handle' => 'sale',
                    'description_html' => '<p>Great deals on selected items.</p>',
                ],
            ];

            foreach ($fashionCollections as $data) {
                Collection::firstOrCreate(
                    ['store_id' => $fashion->id, 'handle' => $data['handle']],
                    [
                        'title' => $data['title'],
                        'description_html' => $data['description_html'],
                        'type' => 'manual',
                        'status' => 'active',
                    ],
                );
            }

            // Acme Electronics collections
            $electronicsCollections = [
                ['title' => 'Featured', 'handle' => 'featured'],
                ['title' => 'Accessories', 'handle' => 'accessories'],
            ];

            foreach ($electronicsCollections as $data) {
                Collection::firstOrCreate(
                    ['store_id' => $electronics->id, 'handle' => $data['handle']],
                    [
                        'title' => $data['title'],
                        'description_html' => null,
                        'type' => 'manual',
                        'status' => 'active',
                    ],
                );
            }
        });
    }
}
