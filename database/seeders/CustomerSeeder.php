<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();
        app()->instance('current_store', $store);

        $customers = [
            ['email' => 'customer@acme.test', 'name' => 'John Doe', 'marketing_opt_in' => true],
            ['email' => 'jane@example.com', 'name' => 'Jane Smith', 'marketing_opt_in' => false],
            ['email' => 'michael@example.com', 'name' => 'Michael Brown', 'marketing_opt_in' => true],
            ['email' => 'sarah@example.com', 'name' => 'Sarah Wilson', 'marketing_opt_in' => false],
            ['email' => 'david@example.com', 'name' => 'David Lee', 'marketing_opt_in' => true],
            ['email' => 'emma@example.com', 'name' => 'Emma Garcia', 'marketing_opt_in' => false],
            ['email' => 'james@example.com', 'name' => 'James Taylor', 'marketing_opt_in' => false],
            ['email' => 'lisa@example.com', 'name' => 'Lisa Anderson', 'marketing_opt_in' => true],
            ['email' => 'robert@example.com', 'name' => 'Robert Martinez', 'marketing_opt_in' => false],
            ['email' => 'anna@example.com', 'name' => 'Anna Thomas', 'marketing_opt_in' => true],
        ];

        foreach ($customers as $data) {
            Customer::create(array_merge($data, [
                'store_id' => $store->id,
                'password' => 'password',
            ]));
        }

        // Add addresses for customer 1
        $customer = Customer::where('email', 'customer@acme.test')
            ->where('store_id', $store->id)
            ->first();

        if ($customer) {
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address1' => 'Hauptstrasse 1',
                    'city' => 'Berlin',
                    'province' => 'Berlin',
                    'zip' => '10115',
                    'country' => 'DE',
                    'phone' => '+49 30 12345678',
                ],
            ]);

            CustomerAddress::create([
                'customer_id' => $customer->id,
                'label' => 'Office',
                'is_default' => false,
                'address_json' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address1' => 'Friedrichstrasse 100',
                    'city' => 'Berlin',
                    'province' => 'Berlin',
                    'zip' => '10117',
                    'country' => 'DE',
                    'phone' => '+49 30 87654321',
                ],
            ]);
        }
    }
}
