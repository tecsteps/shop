<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->where('billing_email', 'billing@acme.test')->firstOrFail();

        foreach ([
            ['handle' => 'acme-fashion', 'name' => 'Acme Fashion', 'currency' => 'EUR'],
            ['handle' => 'acme-electronics', 'name' => 'Acme Electronics', 'currency' => 'EUR'],
        ] as $store) {
            Store::query()->updateOrCreate(
                ['handle' => $store['handle']],
                [
                    'organization_id' => $organization->id,
                    'name' => $store['name'],
                    'status' => StoreStatus::Active,
                    'default_currency' => $store['currency'],
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
            );
        }
    }
}
