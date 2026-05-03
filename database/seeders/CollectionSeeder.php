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
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ([
            [$fashion, 'summer-essentials', 'Summer Essentials', 'Lightweight staples for warm days.'],
            [$fashion, 't-shirts', 'T-Shirts', 'Soft tees and everyday jersey staples.'],
            [$fashion, 'new-arrivals', 'New Arrivals', 'Fresh pieces from the latest Acme drop.'],
            [$fashion, 'denim', 'Denim', 'Jeans, jackets, and structured cotton layers.'],
            [$fashion, 'accessories', 'Accessories', 'Bags, caps, socks, and finishing touches.'],
            [$electronics, 'desk-setup', 'Desk Setup', 'Monitors, stands, and workspace essentials.'],
            [$electronics, 'audio', 'Audio', 'Headphones and speakers for focused work.'],
        ] as [$store, $handle, $title, $description]) {
            Collection::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'handle' => $handle,
                ],
                [
                    'title' => $title,
                    'description_html' => '<p>'.$description.'</p>',
                    'type' => 'manual',
                    'status' => CollectionStatus::Active,
                ],
            );
        }
    }
}
