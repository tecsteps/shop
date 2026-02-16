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
        $organization = Organization::where('slug', 'acme-corp')->firstOrFail();

        Store::firstOrCreate(
            ['slug' => 'acme-fashion'],
            [
                'organization_id' => $organization->id,
                'name' => 'Acme Fashion',
                'status' => StoreStatus::Active,
                'currency' => 'EUR',
            ]
        );

        Store::firstOrCreate(
            ['slug' => 'acme-electronics'],
            [
                'organization_id' => $organization->id,
                'name' => 'Acme Electronics',
                'status' => StoreStatus::Active,
                'currency' => 'EUR',
            ]
        );
    }
}
