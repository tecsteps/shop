<?php

namespace Database\Seeders;

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
        $organization = Organization::query()->where('slug', 'acme-corp')->firstOrFail();

        foreach ([
            ['name' => 'Acme Fashion', 'handle' => 'acme-fashion'],
            ['name' => 'Acme Electronics', 'handle' => 'acme-electronics'],
        ] as $store) {
            Store::query()->updateOrCreate(
                ['handle' => $store['handle']],
                ['organization_id' => $organization->getKey(), 'name' => $store['name'], 'status' => 'active', 'default_currency' => 'EUR', 'default_locale' => 'en', 'timezone' => 'Europe/Berlin', 'metadata' => []],
            );
        }
    }
}
