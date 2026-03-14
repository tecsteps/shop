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
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();

        $fashionCustomers = [
            ['name' => 'John Doe', 'email' => 'customer@acme.test', 'marketing_opt_in' => true],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'marketing_opt_in' => false],
            ['name' => 'Michael Brown', 'email' => 'michael@example.com', 'marketing_opt_in' => true],
            ['name' => 'Sarah Wilson', 'email' => 'sarah@example.com', 'marketing_opt_in' => false],
            ['name' => 'David Lee', 'email' => 'david@example.com', 'marketing_opt_in' => true],
            ['name' => 'Emma Garcia', 'email' => 'emma@example.com', 'marketing_opt_in' => false],
            ['name' => 'James Taylor', 'email' => 'james@example.com', 'marketing_opt_in' => false],
            ['name' => 'Lisa Anderson', 'email' => 'lisa@example.com', 'marketing_opt_in' => true],
            ['name' => 'Robert Martinez', 'email' => 'robert@example.com', 'marketing_opt_in' => false],
            ['name' => 'Anna Thomas', 'email' => 'anna@example.com', 'marketing_opt_in' => true],
        ];

        foreach ($fashionCustomers as $data) {
            $customer = Customer::create([
                'store_id' => $fashion->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => Hash::make('password'),
                'marketing_opt_in' => $data['marketing_opt_in'],
            ]);

            if ($data['email'] === 'customer@acme.test') {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address1' => 'Hauptstrasse 1',
                    'city' => 'Berlin',
                    'postal_code' => '10115',
                    'country_code' => 'DE',
                    'phone' => '+49 30 12345678',
                    'is_default' => true,
                ]);

                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address1' => 'Friedrichstrasse 100',
                    'address2' => '3rd Floor',
                    'city' => 'Berlin',
                    'postal_code' => '10117',
                    'country_code' => 'DE',
                    'phone' => '+49 30 87654321',
                    'is_default' => false,
                ]);
            } elseif ($data['email'] === 'jane@example.com') {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'address1' => 'Schillerstrasse 45',
                    'city' => 'Munich',
                    'province' => 'Bavaria',
                    'postal_code' => '80336',
                    'country_code' => 'DE',
                    'is_default' => true,
                ]);
            } else {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'first_name' => explode(' ', $data['name'])[0],
                    'last_name' => explode(' ', $data['name'])[1],
                    'address1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'postal_code' => fake()->postcode(),
                    'country_code' => 'DE',
                    'is_default' => true,
                ]);
            }
        }

        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        $electronicsCustomers = [
            ['name' => 'Tech Fan', 'email' => 'techfan@example.com'],
            ['name' => 'Gadget Lover', 'email' => 'gadgetlover@example.com'],
        ];

        foreach ($electronicsCustomers as $data) {
            $customer = Customer::create([
                'store_id' => $electronics->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => Hash::make('password'),
                'marketing_opt_in' => false,
            ]);

            CustomerAddress::create([
                'customer_id' => $customer->id,
                'first_name' => explode(' ', $data['name'])[0],
                'last_name' => explode(' ', $data['name'])[1],
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'postal_code' => fake()->postcode(),
                'country_code' => 'DE',
                'is_default' => true,
            ]);
        }
    }
}
