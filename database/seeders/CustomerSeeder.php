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

        $this->seedFashionCustomers($fashion);
        $this->seedElectronicsCustomers($electronics);
    }

    protected function seedFashionCustomers(Store $store): void
    {
        // Customer 1: John Doe (primary test customer)
        $c1 = Customer::factory()->create([
            'store_id' => $store->id,
            'email' => 'customer@acme.test',
            'name' => 'John Doe',
            'password' => 'password',
            'marketing_opt_in' => true,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $c1->id,
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
            'customer_id' => $c1->id,
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

        // Customer 2: Jane Smith
        $c2 = Customer::factory()->create([
            'store_id' => $store->id,
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
            'password' => 'password',
            'marketing_opt_in' => false,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $c2->id,
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

        // Customers 3-10
        $otherCustomers = [
            ['email' => 'michael@example.com', 'name' => 'Michael Brown', 'marketing' => true],
            ['email' => 'sarah@example.com', 'name' => 'Sarah Wilson', 'marketing' => false],
            ['email' => 'david@example.com', 'name' => 'David Lee', 'marketing' => true],
            ['email' => 'emma@example.com', 'name' => 'Emma Garcia', 'marketing' => false],
            ['email' => 'james@example.com', 'name' => 'James Taylor', 'marketing' => false],
            ['email' => 'lisa@example.com', 'name' => 'Lisa Anderson', 'marketing' => true],
            ['email' => 'robert@example.com', 'name' => 'Robert Martinez', 'marketing' => false],
            ['email' => 'anna@example.com', 'name' => 'Anna Thomas', 'marketing' => true],
        ];

        foreach ($otherCustomers as $data) {
            $customer = Customer::factory()->create([
                'store_id' => $store->id,
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => 'password',
                'marketing_opt_in' => $data['marketing'],
            ]);

            $names = explode(' ', $data['name']);
            CustomerAddress::factory()->create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => $names[0],
                    'last_name' => $names[1],
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

    protected function seedElectronicsCustomers(Store $store): void
    {
        $techFan = Customer::factory()->create([
            'store_id' => $store->id,
            'email' => 'techfan@example.com',
            'name' => 'Tech Fan',
            'password' => 'password',
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $techFan->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'Tech',
                'last_name' => 'Fan',
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

        $gadgetLover = Customer::factory()->create([
            'store_id' => $store->id,
            'email' => 'gadgetlover@example.com',
            'name' => 'Gadget Lover',
            'password' => 'password',
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $gadgetLover->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'Gadget',
                'last_name' => 'Lover',
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
