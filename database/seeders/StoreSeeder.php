<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Seed the two demo stores under Acme Corp.
     */
    public function run(): void
    {
        $organization = Organization::query()->where('billing_email', 'billing@acme.test')->firstOrFail();

        $stores = [
            ['name' => 'Acme Fashion', 'handle' => 'acme-fashion'],
            ['name' => 'Acme Electronics', 'handle' => 'acme-electronics'],
        ];

        foreach ($stores as $store) {
            Store::query()->updateOrCreate(
                ['handle' => $store['handle']],
                [
                    'organization_id' => $organization->getKey(),
                    'name' => $store['name'],
                    'status' => 'active',
                    'default_currency' => 'EUR',
                    'default_locale' => 'en',
                    'timezone' => 'Europe/Berlin',
                ],
            );
        }
    }
}
