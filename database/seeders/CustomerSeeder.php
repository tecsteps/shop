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
        $this->seedAcmeFashionCustomers();
        $this->seedAcmeElectronicsCustomers();
    }

    private function seedAcmeFashionCustomers(): void
    {
        $store = Store::where('handle', 'acme-fashion')->first();
        if (! $store) {
            return;
        }

        // Customer 1: Primary test customer
        $customer1 = Customer::factory()->create([
            'store_id' => $store->id,
            'email' => 'customer@acme.test',
            'name' => 'John Doe',
            'password' => 'password',
            'marketing_opt_in' => true,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer1->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => 'Hauptstrasse 1',
                'address2' => null,
                'city' => 'Berlin',
                'province' => null,
                'country_code' => 'DE',
                'zip' => '10115',
                'phone' => '+49 30 12345678',
            ],
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer1->id,
            'label' => 'Work',
            'is_default' => false,
            'address_json' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Acme Corp',
                'address1' => 'Friedrichstrasse 100',
                'address2' => '3rd Floor',
                'city' => 'Berlin',
                'province' => null,
                'country_code' => 'DE',
                'zip' => '10117',
                'phone' => '+49 30 87654321',
            ],
        ]);

        // Customer 2
        $customer2 = Customer::factory()->create([
            'store_id' => $store->id,
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
            'password' => 'password',
            'marketing_opt_in' => false,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer2->id,
            'label' => 'Home',
            'is_default' => true,
            'address_json' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'address1' => 'Schillerstrasse 45',
                'city' => 'Munich',
                'province' => 'BY',
                'country_code' => 'DE',
                'zip' => '80336',
            ],
        ]);

        // Customers 3-10
        $otherCustomers = [
            ['email' => 'michael@example.com', 'name' => 'Michael Brown', 'opt_in' => true],
            ['email' => 'sarah@example.com', 'name' => 'Sarah Wilson', 'opt_in' => false],
            ['email' => 'david@example.com', 'name' => 'David Lee', 'opt_in' => true],
            ['email' => 'emma@example.com', 'name' => 'Emma Garcia', 'opt_in' => false],
            ['email' => 'james@example.com', 'name' => 'James Taylor', 'opt_in' => false],
            ['email' => 'lisa@example.com', 'name' => 'Lisa Anderson', 'opt_in' => true],
            ['email' => 'robert@example.com', 'name' => 'Robert Martinez', 'opt_in' => false],
            ['email' => 'anna@example.com', 'name' => 'Anna Thomas', 'opt_in' => true],
        ];

        foreach ($otherCustomers as $data) {
            $customer = Customer::factory()->create([
                'store_id' => $store->id,
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => 'password',
                'marketing_opt_in' => $data['opt_in'],
            ]);

            $nameParts = explode(' ', $data['name']);
            CustomerAddress::factory()->create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'address1' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'country_code' => 'DE',
                    'zip' => fake()->postcode(),
                ],
            ]);
        }
    }

    private function seedAcmeElectronicsCustomers(): void
    {
        $store = Store::where('handle', 'acme-electronics')->first();
        if (! $store) {
            return;
        }

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
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
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
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
            ],
        ]);
    }
}
