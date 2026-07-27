<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectionSeeder extends Seeder
{
    /**
     * Create the product collections (spec 07 §3.9).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $collections = [
                [$fashion->id, 'New Arrivals', 'new-arrivals', 'Discover the latest additions to our store.'],
                [$fashion->id, 'T-Shirts', 't-shirts', 'Premium cotton tees for every occasion.'],
                [$fashion->id, 'Pants & Jeans', 'pants-jeans', 'Find the perfect fit from our denim and trouser range.'],
                [$fashion->id, 'Sale', 'sale', 'Great deals on selected items.'],
                [$electronics->id, 'Featured', 'featured', 'Our featured products.'],
                [$electronics->id, 'Accessories', 'accessories', 'Cables, stands, and other accessories.'],
            ];

            foreach ($collections as [$storeId, $title, $handle, $description]) {
                Collection::query()->updateOrCreate(
                    ['store_id' => $storeId, 'handle' => $handle],
                    [
                        'title' => $title,
                        'description_html' => "<p>{$description}</p>",
                        'type' => CollectionType::Manual,
                        'status' => CollectionStatus::Active,
                    ],
                );
            }
        });
    }
}
