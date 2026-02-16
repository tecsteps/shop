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
        $this->seedFashion();
        $this->seedElectronics();
    }

    private function seedFashion(): void
    {
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();

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
            $customer = Customer::firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'password' => 'password',
                    'accepts_marketing' => $data['accepts_marketing'],
                ]
            );

            if ($customer->addresses()->exists()) {
                continue;
            }

            if ($data['email'] === 'customer@acme.test') {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
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
                    'is_default' => true,
                ]);
                CustomerAddress::create([
                    'customer_id' => $customer->id,
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
                    'is_default' => false,
                ]);
            } elseif ($data['email'] === 'jane@example.com') {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
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
                    'is_default' => true,
                ]);
            } else {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'company' => '',
                    'address1' => 'Musterstrasse '.rand(1, 100),
                    'address2' => '',
                    'city' => collect(['Berlin', 'Munich', 'Hamburg', 'Frankfurt', 'Cologne'])->random(),
                    'province' => '',
                    'province_code' => '',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => (string) rand(10000, 99999),
                    'phone' => '',
                    'is_default' => true,
                ]);
            }
        }
    }

    private function seedElectronics(): void
    {
        $store = Store::where('slug', 'acme-electronics')->firstOrFail();

        $customers = [
            ['email' => 'techfan@example.com', 'first_name' => 'Tech', 'last_name' => 'Fan'],
            ['email' => 'gadgetlover@example.com', 'first_name' => 'Gadget', 'last_name' => 'Lover'],
        ];

        foreach ($customers as $data) {
            $customer = Customer::firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'password' => 'password',
                    'accepts_marketing' => false,
                ]
            );

            if (! $customer->addresses()->exists()) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'company' => '',
                    'address1' => 'Techstrasse '.rand(1, 50),
                    'address2' => '',
                    'city' => 'Berlin',
                    'province' => '',
                    'province_code' => '',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => (string) rand(10000, 99999),
                    'phone' => '',
                    'is_default' => true,
                ]);
            }
        }
    }
}
