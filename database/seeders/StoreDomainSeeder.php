<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Database\Seeder;

class StoreDomainSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->first() ?? Store::factory()->create();

        StoreDomain::query()->firstOrCreate(
            ['hostname' => 'shop.test'],
            [
                'store_id' => $store->id,
                'is_primary' => true,
            ],
        );
    }
}
