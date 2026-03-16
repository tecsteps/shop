<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();

        Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Store',
            'handle' => 'acme-store',
            'default_currency' => 'EUR',
        ]);
    }
}
