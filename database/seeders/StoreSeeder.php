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

        Store::create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'slug' => 'acme-fashion',
            'status' => StoreStatus::Active,
            'currency' => 'USD',
        ]);
    }
}
