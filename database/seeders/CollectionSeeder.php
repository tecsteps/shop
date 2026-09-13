<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $this->seedCollection($fashion, 'New Arrivals', 'new-arrivals', '<p>Discover the latest additions to our store.</p>');
        $this->seedCollection($fashion, 'T-Shirts', 't-shirts', '<p>Premium cotton tees for every occasion.</p>');
        $this->seedCollection($fashion, 'Pants & Jeans', 'pants-jeans', '<p>Find the perfect fit from our denim and trouser range.</p>');
        $this->seedCollection($fashion, 'Sale', 'sale', '<p>Great deals on selected items.</p>');

        $this->seedCollection($electronics, 'Featured', 'featured', '<p>Featured products.</p>');
        $this->seedCollection($electronics, 'Accessories', 'accessories', '<p>Accessories for your setup.</p>');
    }

    protected function seedCollection(Store $store, string $title, string $handle, string $description): void
    {
        Collection::query()->updateOrCreate(
            ['store_id' => $store->getKey(), 'handle' => $handle],
            [
                'title' => $title,
                'description_html' => $description,
                'type' => CollectionType::Manual,
                'status' => CollectionStatus::Active,
            ]
        );
    }
}
