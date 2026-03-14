<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'Acme Corp')->firstOrFail();

        Store::create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
            'status' => StoreStatus::Active,
            'default_currency' => 'EUR',
        ]);

        Store::create([
            'organization_id' => $organization->id,
            'name' => 'Acme Electronics',
            'handle' => 'acme-electronics',
            'status' => StoreStatus::Active,
            'default_currency' => 'EUR',
        ]);
    }
}
