<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectionSeeder extends Seeder
{
    /**
     * Create product collections for each store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCollections('acme-fashion', [
                [
                    'title' => 'New Arrivals',
                    'handle' => 'new-arrivals',
                    'description' => 'Discover the latest additions to our store.',
                ],
                [
                    'title' => 'T-Shirts',
                    'handle' => 't-shirts',
                    'description' => 'Premium cotton tees for every occasion.',
                ],
                [
                    'title' => 'Pants & Jeans',
                    'handle' => 'pants-jeans',
                    'description' => 'Find the perfect fit from our denim and trouser range.',
                ],
                [
                    'title' => 'Sale',
                    'handle' => 'sale',
                    'description' => 'Great deals on selected items.',
                ],
            ]);

            $this->seedCollections('acme-electronics', [
                ['title' => 'Featured', 'handle' => 'featured'],
                ['title' => 'Accessories', 'handle' => 'accessories'],
            ]);
        });
    }

    /**
     * @param  array<int, array<string, string>>  $collections
     */
    private function seedCollections(string $storeHandle, array $collections): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        foreach ($collections as $collection) {
            Collection::updateOrCreate(
                ['store_id' => $store->id, 'handle' => $collection['handle']],
                [
                    'title' => $collection['title'],
                    'description_html' => '<p>'.($collection['description'] ?? '').'</p>',
                    'type' => 'manual',
                    'status' => 'active',
                ],
            );
        }
    }
}
