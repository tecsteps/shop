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
        $stores = Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get();

        foreach ($stores as $store) {
            $name = $store->handle === 'acme-fashion' ? 'Acme Fashion' : 'Acme Electronics';
            $email = $store->handle === 'acme-fashion' ? 'hello@acme-fashion.test' : 'hello@acme-electronics.test';
            $start = $store->handle === 'acme-fashion' ? 1001 : 5001;

            StoreSettings::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey()],
                ['settings_json' => ['store_name' => $name, 'contact_email' => $email, 'order_number_prefix' => '#', 'order_number_start' => $start], 'general_json' => ['store_name' => $name], 'checkout_json' => ['guest_checkout' => true], 'notification_json' => [], 'social_json' => []],
            );
        }
    }
}
