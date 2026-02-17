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
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        // Acme Fashion customers
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

        foreach ($fashionCustomers as $data) {
            Customer::factory()->withPassword()->create([
                'store_id' => $fashion->id,
                'email' => $data['email'],
                'name' => $data['name'],
                'marketing_opt_in' => $data['marketing_opt_in'],
            ]);
        }

        // Customer 1 addresses (customer@acme.test)
        $john = Customer::query()
            ->where('store_id', $fashion->id)
            ->where('email', 'customer@acme.test')
            ->firstOrFail();

        CustomerAddress::factory()->create([
            'customer_id' => $john->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => '',
                'address1' => 'Hauptstrasse 1',
                'address2' => '',
                'city' => 'Berlin',
                'province' => '',
                'province_code' => '',
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
                'province' => '',
                'province_code' => '',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => '10117',
                'phone' => '+49 30 87654321',
            ],
        ]);

        // Customer 2 addresses (jane@example.com)
        $jane = Customer::query()
            ->where('store_id', $fashion->id)
            ->where('email', 'jane@example.com')
            ->firstOrFail();

        CustomerAddress::factory()->create([
            'customer_id' => $jane->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'company' => '',
                'address1' => 'Schillerstrasse 45',
                'address2' => '',
                'city' => 'Munich',
                'province' => 'Bavaria',
                'province_code' => 'BY',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => '80336',
                'phone' => '',
            ],
        ]);

        // Customers 3-10: default address with German data
        $remainingCustomers = Customer::query()
            ->where('store_id', $fashion->id)
            ->whereNotIn('email', ['customer@acme.test', 'jane@example.com'])
            ->get();

        $germanCities = [
            ['city' => 'Hamburg', 'zip' => '20095', 'street' => 'Monckebergstrasse 12'],
            ['city' => 'Cologne', 'zip' => '50667', 'street' => 'Hohe Strasse 88'],
            ['city' => 'Frankfurt', 'zip' => '60311', 'street' => 'Zeil 34'],
            ['city' => 'Stuttgart', 'zip' => '70173', 'street' => 'Konigstrasse 22'],
            ['city' => 'Dusseldorf', 'zip' => '40213', 'street' => 'Schadowstrasse 56'],
            ['city' => 'Leipzig', 'zip' => '04109', 'street' => 'Grimmaische Strasse 10'],
            ['city' => 'Dresden', 'zip' => '01067', 'street' => 'Prager Strasse 4'],
            ['city' => 'Nuremberg', 'zip' => '90402', 'street' => 'Breite Gasse 76'],
        ];

        foreach ($remainingCustomers as $i => $customer) {
            $parts = explode(' ', $customer->name, 2);
            $cityData = $germanCities[$i % count($germanCities)];

            CustomerAddress::factory()->create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => $parts[0],
                    'last_name' => $parts[1] ?? '',
                    'company' => '',
                    'address1' => $cityData['street'],
                    'address2' => '',
                    'city' => $cityData['city'],
                    'province' => '',
                    'province_code' => '',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => $cityData['zip'],
                    'phone' => '',
                ],
            ]);
        }

        // Acme Electronics customers
        $elecCustomers = [
            ['email' => 'techfan@example.com', 'name' => 'Tech Fan'],
            ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover'],
        ];

        foreach ($elecCustomers as $data) {
            $customer = Customer::factory()->withPassword()->create([
                'store_id' => $electronics->id,
                'email' => $data['email'],
                'name' => $data['name'],
            ]);

            $parts = explode(' ', $data['name'], 2);
            CustomerAddress::factory()->create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => $parts[0],
                    'last_name' => $parts[1] ?? '',
                    'company' => '',
                    'address1' => fake()->streetAddress(),
                    'address2' => '',
                    'city' => fake()->city(),
                    'province' => '',
                    'province_code' => '',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => fake()->postcode(),
                    'phone' => '',
                ],
            ]);
        }
    }
}
