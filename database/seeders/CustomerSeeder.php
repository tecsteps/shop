<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        Customer::withoutGlobalScopes()->updateOrCreate(
            [
                'store_id' => $store->getKey(),
                'email' => 'customer@acme.test',
            ],
            [
                'name' => 'John Doe',
                'password' => 'password',
                'marketing_opt_in' => true,
            ],
        );
    }
}
