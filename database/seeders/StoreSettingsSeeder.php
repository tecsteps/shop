<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->each(function (Store $store): void {
            StoreSettings::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                [
                    'settings_json' => [
                        'announcement' => [
                            'enabled' => true,
                            'text' => 'Free shipping on orders over 75.00 EUR',
                        ],
                        'checkout' => [
                            'guest_checkout_enabled' => true,
                        ],
                    ],
                ],
            );
        });
    }
}
