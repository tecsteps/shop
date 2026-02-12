<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');
        $electronics = Store::query()->firstWhere('handle', 'acme-electronics');

        if ($fashion) {
            $this->upsertCollection($fashion->id, 'New Arrivals', 'new-arrivals', '<p>Discover the latest additions to our store.</p>');
            $this->upsertCollection($fashion->id, 'T-Shirts', 't-shirts', '<p>Premium cotton tees for every occasion.</p>');
            $this->upsertCollection($fashion->id, 'Pants & Jeans', 'pants-jeans', '<p>Find the perfect fit from our denim and trouser range.</p>');
            $this->upsertCollection($fashion->id, 'Sale', 'sale', '<p>Great deals on selected items.</p>');
        }

        if ($electronics) {
            $this->upsertCollection($electronics->id, 'Featured', 'featured', '<p>Top electronics picks from our catalog.</p>');
            $this->upsertCollection($electronics->id, 'Accessories', 'accessories', '<p>Accessories for every device.</p>');
        }
    }

    private function upsertCollection(int $storeId, string $title, string $handle, string $description): void
    {
        Collection::query()->updateOrCreate(
            ['store_id' => $storeId, 'handle' => $handle],
            [
                'title' => $title,
                'description_html' => $description,
                'type' => 'manual',
                'status' => 'active',
            ]
        );
    }
}
