<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $collections = [
            [
                'title' => 'T-Shirts',
                'handle' => 't-shirts',
                'description_html' => 'Our collection of premium t-shirts.',
                'status' => CollectionStatus::Active,
                'published_at' => now(),
            ],
            [
                'title' => 'New Arrivals',
                'handle' => 'new-arrivals',
                'description_html' => 'Check out our latest products.',
                'status' => CollectionStatus::Active,
                'published_at' => now(),
            ],
            [
                'title' => 'Sale',
                'handle' => 'sale',
                'description_html' => 'Great deals on selected items.',
                'status' => CollectionStatus::Active,
                'published_at' => now(),
            ],
        ];

        foreach ($collections as $collection) {
            Collection::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                ...$collection,
            ]);
        }
    }
}
