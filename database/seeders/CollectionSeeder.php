<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $data = [
                'acme-fashion' => [
                    ['New Arrivals', 'new-arrivals', '<p>Discover the latest additions to our store.</p>'],
                    ['T-Shirts', 't-shirts', '<p>Premium cotton tees for every occasion.</p>'],
                    ['Pants & Jeans', 'pants-jeans', '<p>Find the perfect fit from our denim and trouser range.</p>'],
                    ['Sale', 'sale', '<p>Great deals on selected items.</p>'],
                ],
                'acme-electronics' => [
                    ['Featured', 'featured', null],
                    ['Accessories', 'accessories', null],
                ],
            ];
            foreach ($data as $handle => $collections) {
                $store = Store::query()->where('handle', $handle)->sole();
                foreach ($collections as [$title, $collectionHandle, $description]) {
                    Collection::query()->updateOrCreate(
                        ['store_id' => $store->id, 'handle' => $collectionHandle],
                        ['title' => $title, 'description_html' => $description, 'type' => 'manual', 'status' => 'active'],
                    );
                }
            }
        });
    }
}
