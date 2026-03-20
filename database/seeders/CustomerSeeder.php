<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::first();

        Customer::factory()->create([
            'store_id' => $store->id,
            'name' => 'John Doe',
            'email' => 'customer@acme.test',
            'password' => 'password',
        ]);
    }
}
