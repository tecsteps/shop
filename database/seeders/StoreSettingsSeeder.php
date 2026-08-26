<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSettingsSeeder extends Seeder
{
    /**
     * Insert per-store JSON settings (store name, contact email, order numbering).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSettings('acme-fashion', [
                'store_name' => 'Acme Fashion',
                'contact_email' => 'hello@acme-fashion.test',
                'order_number_prefix' => '#',
                'order_number_start' => 1001,
            ]);

            $this->seedSettings('acme-electronics', [
                'store_name' => 'Acme Electronics',
                'contact_email' => 'hello@acme-electronics.test',
                'order_number_prefix' => '#',
                'order_number_start' => 5001,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function seedSettings(string $storeHandle, array $settings): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        StoreSettings::updateOrCreate(
            ['store_id' => $store->id],
            ['settings_json' => $settings, 'updated_at' => now()],
        );
    }
}
