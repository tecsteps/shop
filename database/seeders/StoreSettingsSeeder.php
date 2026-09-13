<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'acme-fashion' => [
                'store_name' => 'Acme Fashion',
                'contact_email' => 'hello@acme-fashion.test',
                'order_number_prefix' => '#',
                'order_number_start' => 1001,
            ],
            'acme-electronics' => [
                'store_name' => 'Acme Electronics',
                'contact_email' => 'hello@acme-electronics.test',
                'order_number_prefix' => '#',
                'order_number_start' => 5001,
            ],
        ];

        foreach ($settings as $handle => $values) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();

            StoreSetting::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                ['settings_json' => $values]
            );
        }
    }
}
