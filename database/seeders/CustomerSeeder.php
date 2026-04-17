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
        $this->seedFashionCustomers();
        $this->seedElectronicsCustomers();
    }

    private function seedFashionCustomers(): void
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
            Customer::firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                array_merge($data, ['store_id' => $store->id, 'password' => 'password'])
            );
        }

        // Customer 1 (John Doe) - Home + Work addresses
        $john = Customer::where('email', 'customer@acme.test')->where('store_id', $store->id)->first();
        if ($john && $john->addresses()->count() === 0) {
            CustomerAddress::create([
                'customer_id' => $john->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => 'John', 'last_name' => 'Doe', 'company' => '',
                    'address1' => 'Hauptstrasse 1', 'address2' => '',
                    'city' => 'Berlin', 'province' => '', 'province_code' => '',
                    'country' => 'Germany', 'country_code' => 'DE',
                    'zip' => '10115', 'phone' => '+49 30 12345678',
                ],
            ]);

            CustomerAddress::create([
                'customer_id' => $john->id,
                'label' => 'Work',
                'is_default' => false,
                'address_json' => [
                    'first_name' => 'John', 'last_name' => 'Doe', 'company' => 'Acme Corp',
                    'address1' => 'Friedrichstrasse 100', 'address2' => '3rd Floor',
                    'city' => 'Berlin', 'province' => '', 'province_code' => '',
                    'country' => 'Germany', 'country_code' => 'DE',
                    'zip' => '10117', 'phone' => '+49 30 87654321',
                ],
            ]);
        }

        // Customer 2 (Jane Smith) - Home address
        $jane = Customer::where('email', 'jane@example.com')->where('store_id', $store->id)->first();
        if ($jane && $jane->addresses()->count() === 0) {
            CustomerAddress::create([
                'customer_id' => $jane->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => 'Jane', 'last_name' => 'Smith', 'company' => '',
                    'address1' => 'Schillerstrasse 45', 'address2' => '',
                    'city' => 'Munich', 'province' => 'Bavaria', 'province_code' => 'BY',
                    'country' => 'Germany', 'country_code' => 'DE',
                    'zip' => '80336', 'phone' => '',
                ],
            ]);
        }

        // Customers 3-10 - Default German addresses
        $addressData = [
            ['michael@example.com', 'Michael', 'Brown', 'Alexanderplatz 3', '10178'],
            ['sarah@example.com', 'Sarah', 'Wilson', 'Kurfuerstendamm 21', '10719'],
            ['david@example.com', 'David', 'Lee', 'Unter den Linden 7', '10117'],
            ['emma@example.com', 'Emma', 'Garcia', 'Potsdamer Strasse 12', '10785'],
            ['james@example.com', 'James', 'Taylor', 'Torstrasse 89', '10119'],
            ['lisa@example.com', 'Lisa', 'Anderson', 'Oranienstrasse 34', '10999'],
            ['robert@example.com', 'Robert', 'Martinez', 'Kastanienallee 56', '10435'],
            ['anna@example.com', 'Anna', 'Thomas', 'Bergmannstrasse 78', '10961'],
        ];

        foreach ($addressData as [$email, $first, $last, $street, $zip]) {
            $customer = Customer::where('email', $email)->where('store_id', $store->id)->first();
            if ($customer && $customer->addresses()->count() === 0) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => 'Home',
                    'is_default' => true,
                    'address_json' => [
                        'first_name' => $first, 'last_name' => $last, 'company' => '',
                        'address1' => $street, 'address2' => '',
                        'city' => 'Berlin', 'province' => '', 'province_code' => '',
                        'country' => 'Germany', 'country_code' => 'DE',
                        'zip' => $zip, 'phone' => '',
                    ],
                ]);
            }
        }
    }

    private function seedElectronicsCustomers(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();
        app()->instance('current_store', $store);

        $customers = [
            ['email' => 'techfan@example.com', 'name' => 'Tech Fan'],
            ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover'],
        ];

        foreach ($customers as $data) {
            $customer = Customer::firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                array_merge($data, ['store_id' => $store->id, 'password' => 'password', 'marketing_opt_in' => false])
            );

            if ($customer->addresses()->count() === 0) {
                [$first, $last] = explode(' ', $data['name'], 2);
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => 'Home',
                    'is_default' => true,
                    'address_json' => [
                        'first_name' => $first, 'last_name' => $last, 'company' => '',
                        'address1' => 'Musterweg '.rand(1, 50), 'address2' => '',
                        'city' => 'Hamburg', 'province' => '', 'province_code' => '',
                        'country' => 'Germany', 'country_code' => 'DE',
                        'zip' => '20095', 'phone' => '',
                    ],
                ]);
            }
        }
    }
}
