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
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        StoreSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'settings_json' => [
                    'checkout' => [
                        'guest_checkout_enabled' => true,
                    ],
                    'notifications' => [
                        'order_confirmation' => true,
                    ],
                    'order_number_prefix' => '#',
                    'bank_transfer_cancel_days' => 7,
                ],
            ],
        );
    }
}
