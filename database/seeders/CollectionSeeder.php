<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollectionSeeder extends Seeder
{
    /**
     * Seed product collections for both stores.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedAcmeFashion();
            $this->seedAcmeElectronics();
        });
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        /** @var array<int, array{title: string, handle: string, description_html: string}> $collections */
        $collections = [
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

        foreach ($collections as $data) {
            Collection::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $data['handle']],
                [
                    'title' => $data['title'],
                    'description_html' => $data['description_html'],
                    'type' => 'manual',
                    'status' => 'active',
                ],
            );
        }
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();

        $collections = [
            ['title' => 'Featured', 'handle' => 'featured', 'description_html' => '<p>Our top picks for you.</p>'],
            ['title' => 'Accessories', 'handle' => 'accessories', 'description_html' => '<p>Essential tech accessories.</p>'],
        ];

        foreach ($collections as $data) {
            Collection::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $data['handle']],
                [
                    'title' => $data['title'],
                    'description_html' => $data['description_html'],
                    'type' => 'manual',
                    'status' => 'active',
                ],
            );
        }
    }
}
