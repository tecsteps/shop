<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        $customer = Customer::create([
            'store_id' => $store->id,
            'name' => 'John Doe',
            'email' => 'customer@acme.test',
            'password_hash' => Hash::make('password'),
            'marketing_opt_in' => false,
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Alexanderplatz 1',
            'city' => 'Berlin',
            'postal_code' => '10178',
            'country_code' => 'DE',
            'phone' => '+49 30 1234567',
            'is_default' => true,
        ]);

        CustomerAddress::create([
            'customer_id' => $customer->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Marienplatz 10',
            'city' => 'Munich',
            'postal_code' => '80331',
            'country_code' => 'DE',
            'phone' => '+49 89 9876543',
            'is_default' => false,
        ]);
    }
}
