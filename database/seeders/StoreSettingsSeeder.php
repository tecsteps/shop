<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->first() ?? Store::factory()->create();

        StoreSettings::query()->firstOrCreate(
            ['store_id' => $store->id],
            [
                'settings_json' => [
                    'contact_email' => 'hello@example.com',
                ],
            ],
        );
    }
}
