<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->first() ?? Organization::factory()->create();

        Store::query()->firstOrCreate(
            ['handle' => 'demo-shop'],
            [
                'organization_id' => $organization->id,
                'name' => 'Demo Shop',
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
        );
    }
}
