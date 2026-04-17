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
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        app()->instance('current_store', $fashion);

        // Acme Fashion - 10 customers
        $fashionCustomers = [
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

        foreach ($fashionCustomers as $c) {
            Customer::factory()->create([
                'store_id' => $fashion->id,
                'email' => $c['email'],
                'name' => $c['name'],
                'password' => 'password',
                'marketing_opt_in' => $c['marketing_opt_in'],
            ]);
        }

        // Customer 1 (John Doe) - 2 addresses
        $john = Customer::where('email', 'customer@acme.test')->first();
        CustomerAddress::factory()->create([
            'customer_id' => $john->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => null,
                'address1' => 'Hauptstrasse 1',
                'address2' => null,
                'city' => 'Berlin',
                'province' => 'Berlin',
                'province_code' => 'BE',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => '10115',
                'phone' => '+49 30 12345678',
            ],
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $john->id,
            'label' => 'Work',
            'is_default' => false,
            'address_json' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Acme Corp',
                'address1' => 'Friedrichstrasse 100',
                'address2' => '3rd Floor',
                'city' => 'Berlin',
                'province' => 'Berlin',
                'province_code' => 'BE',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => '10117',
                'phone' => '+49 30 87654321',
            ],
        ]);

        // Customer 2 (Jane Smith) - 1 address
        $jane = Customer::where('email', 'jane@example.com')->first();
        CustomerAddress::factory()->create([
            'customer_id' => $jane->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'company' => null,
                'address1' => 'Schillerstrasse 45',
                'address2' => null,
                'city' => 'Munich',
                'province' => 'Bavaria',
                'province_code' => 'BY',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => '80336',
                'phone' => null,
            ],
        ]);

        // Customers 3-10: one default address each
        $remainingCustomers = Customer::where('store_id', $fashion->id)
            ->whereNotIn('email', ['customer@acme.test', 'jane@example.com'])
            ->get();

        $germanCities = [
            ['city' => 'Hamburg', 'zip' => '20095', 'province' => 'Hamburg', 'code' => 'HH'],
            ['city' => 'Frankfurt', 'zip' => '60311', 'province' => 'Hesse', 'code' => 'HE'],
            ['city' => 'Cologne', 'zip' => '50667', 'province' => 'North Rhine-Westphalia', 'code' => 'NW'],
            ['city' => 'Stuttgart', 'zip' => '70173', 'province' => 'Baden-Wuerttemberg', 'code' => 'BW'],
            ['city' => 'Dusseldorf', 'zip' => '40213', 'province' => 'North Rhine-Westphalia', 'code' => 'NW'],
            ['city' => 'Leipzig', 'zip' => '04109', 'province' => 'Saxony', 'code' => 'SN'],
            ['city' => 'Dresden', 'zip' => '01067', 'province' => 'Saxony', 'code' => 'SN'],
            ['city' => 'Nuremberg', 'zip' => '90402', 'province' => 'Bavaria', 'code' => 'BY'],
        ];

        foreach ($remainingCustomers as $idx => $customer) {
            $nameParts = explode(' ', $customer->name);
            $cityData = $germanCities[$idx % count($germanCities)];

            CustomerAddress::factory()->create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'company' => null,
                    'address1' => 'Musterstrasse '.($idx + 10),
                    'address2' => null,
                    'city' => $cityData['city'],
                    'province' => $cityData['province'],
                    'province_code' => $cityData['code'],
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => $cityData['zip'],
                    'phone' => null,
                ],
            ]);
        }

        // Acme Electronics - 2 customers
        app()->instance('current_store', $electronics);

        $elecCustomers = [
            ['email' => 'techfan@example.com', 'name' => 'Tech Fan'],
            ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover'],
        ];

        foreach ($elecCustomers as $c) {
            $customer = Customer::factory()->create([
                'store_id' => $electronics->id,
                'email' => $c['email'],
                'name' => $c['name'],
                'password' => 'password',
            ]);

            $nameParts = explode(' ', $c['name']);
            CustomerAddress::factory()->create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'company' => null,
                    'address1' => 'Techstrasse '.rand(1, 99),
                    'address2' => null,
                    'city' => 'Berlin',
                    'province' => 'Berlin',
                    'province_code' => 'BE',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => '10115',
                    'phone' => null,
                ],
            ]);
        }
    }
}
