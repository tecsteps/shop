<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        Collection::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'handle' => 'summer-essentials',
            ],
            [
                'title' => 'Summer Essentials',
                'description_html' => '<p>Lightweight staples for warm days.</p>',
                'type' => 'manual',
                'status' => CollectionStatus::Active,
            ],
        );
    }
}
