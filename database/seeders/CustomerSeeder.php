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
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        $this->seedFashionCustomers($fashion);
        $this->seedElectronicsCustomers($electronics);
    }

    private function seedFashionCustomers(Store $store): void
    {
        $customers = [
            ['email' => 'customer@acme.test', 'first_name' => 'John', 'last_name' => 'Doe', 'accepts_marketing' => true],
            ['email' => 'jane@example.com', 'first_name' => 'Jane', 'last_name' => 'Smith', 'accepts_marketing' => false],
            ['email' => 'michael@example.com', 'first_name' => 'Michael', 'last_name' => 'Brown', 'accepts_marketing' => true],
            ['email' => 'sarah@example.com', 'first_name' => 'Sarah', 'last_name' => 'Wilson', 'accepts_marketing' => false],
            ['email' => 'david@example.com', 'first_name' => 'David', 'last_name' => 'Lee', 'accepts_marketing' => true],
            ['email' => 'emma@example.com', 'first_name' => 'Emma', 'last_name' => 'Garcia', 'accepts_marketing' => false],
            ['email' => 'james@example.com', 'first_name' => 'James', 'last_name' => 'Taylor', 'accepts_marketing' => false],
            ['email' => 'lisa@example.com', 'first_name' => 'Lisa', 'last_name' => 'Anderson', 'accepts_marketing' => true],
            ['email' => 'robert@example.com', 'first_name' => 'Robert', 'last_name' => 'Martinez', 'accepts_marketing' => false],
            ['email' => 'anna@example.com', 'first_name' => 'Anna', 'last_name' => 'Thomas', 'accepts_marketing' => true],
        ];

        foreach ($customers as $data) {
            Customer::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                array_merge($data, ['password' => 'password']),
            );
        }

        // Customer 1 addresses (John Doe)
        $john = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', 'customer@acme.test')
            ->firstOrFail();

        CustomerAddress::firstOrCreate(
            ['customer_id' => $john->id, 'is_default' => true],
            [
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
        );

        CustomerAddress::firstOrCreate(
            ['customer_id' => $john->id, 'address1' => 'Friedrichstrasse 100'],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Acme Corp',
                'address2' => '3rd Floor',
                'city' => 'Berlin',
                'province' => '',
                'province_code' => '',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => '10117',
                'phone' => '+49 30 87654321',
                'is_default' => false,
            ],
        );

        // Customer 2 addresses (Jane Smith)
        $jane = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', 'jane@example.com')
            ->firstOrFail();

        CustomerAddress::firstOrCreate(
            ['customer_id' => $jane->id, 'is_default' => true],
            [
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
        );

        // Customers 3-10: each gets one default address
        $remainingCustomers = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNotIn('email', ['customer@acme.test', 'jane@example.com'])
            ->get();

        $germanCities = [
            ['city' => 'Hamburg', 'zip' => '20095'],
            ['city' => 'Frankfurt', 'zip' => '60311'],
            ['city' => 'Cologne', 'zip' => '50667'],
            ['city' => 'Stuttgart', 'zip' => '70173'],
            ['city' => 'Dusseldorf', 'zip' => '40213'],
            ['city' => 'Leipzig', 'zip' => '04109'],
            ['city' => 'Dresden', 'zip' => '01067'],
            ['city' => 'Nuremberg', 'zip' => '90402'],
        ];

        foreach ($remainingCustomers as $index => $customer) {
            $cityData = $germanCities[$index % count($germanCities)];

            CustomerAddress::firstOrCreate(
                ['customer_id' => $customer->id, 'is_default' => true],
                [
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'company' => '',
                    'address1' => 'Musterstrasse '.($index + 10),
                    'address2' => '',
                    'city' => $cityData['city'],
                    'province' => '',
                    'province_code' => '',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => $cityData['zip'],
                    'phone' => '',
                ],
            );
        }
    }

    private function seedElectronicsCustomers(Store $store): void
    {
        $customers = [
            ['email' => 'techfan@example.com', 'first_name' => 'Tech', 'last_name' => 'Fan', 'accepts_marketing' => false],
            ['email' => 'gadgetlover@example.com', 'first_name' => 'Gadget', 'last_name' => 'Lover', 'accepts_marketing' => false],
        ];

        foreach ($customers as $data) {
            $customer = Customer::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                array_merge($data, ['password' => 'password']),
            );

            CustomerAddress::firstOrCreate(
                ['customer_id' => $customer->id, 'is_default' => true],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'company' => '',
                    'address1' => 'Techstrasse '.fake()->numberBetween(1, 99),
                    'address2' => '',
                    'city' => 'Berlin',
                    'province' => '',
                    'province_code' => '',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => '10115',
                    'phone' => '',
                ],
            );
        }
    }
}
