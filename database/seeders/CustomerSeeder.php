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
        $fashion = Store::query()->firstWhere('handle', 'acme-fashion');
        $electronics = Store::query()->firstWhere('handle', 'acme-electronics');

        if ($fashion) {
            $this->seedFashionCustomers($fashion->id);
        }

        if ($electronics) {
            $this->seedElectronicsCustomers($electronics->id);
        }
    }

    private function seedFashionCustomers(int $storeId): void
    {
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
            $customer = Customer::query()->updateOrCreate(
                [
                    'store_id' => $storeId,
                    'email' => $data['email'],
                ],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'marketing_opt_in' => $data['marketing_opt_in'],
                    'email_verified_at' => now(),
                ]
            );

            if ($data['email'] === 'customer@acme.test') {
                $this->seedJohnDoeAddresses($customer->id);

                continue;
            }

            if ($data['email'] === 'jane@example.com') {
                $this->seedJaneAddress($customer->id);

                continue;
            }

            CustomerAddress::query()->updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'label' => 'Home',
                ],
                [
                    'is_default' => true,
                    'address_json' => [
                        'first_name' => explode(' ', $data['name'])[0],
                        'last_name' => explode(' ', $data['name'])[1] ?? '',
                        'address1' => fake()->streetAddress(),
                        'city' => fake()->city(),
                        'province' => fake()->state(),
                        'province_code' => strtoupper(fake()->lexify('??')),
                        'country' => 'Germany',
                        'country_code' => 'DE',
                        'zip' => fake()->postcode(),
                        'phone' => fake()->phoneNumber(),
                    ],
                ]
            );
        }
    }

    private function seedElectronicsCustomers(int $storeId): void
    {
        $customers = [
            ['email' => 'techfan@example.com', 'name' => 'Tech Fan'],
            ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover'],
        ];

        foreach ($customers as $data) {
            $customer = Customer::query()->updateOrCreate(
                [
                    'store_id' => $storeId,
                    'email' => $data['email'],
                ],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'marketing_opt_in' => true,
                    'email_verified_at' => now(),
                ]
            );

            CustomerAddress::query()->updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'label' => 'Home',
                ],
                [
                    'is_default' => true,
                    'address_json' => [
                        'first_name' => explode(' ', $data['name'])[0],
                        'last_name' => explode(' ', $data['name'])[1] ?? '',
                        'address1' => fake()->streetAddress(),
                        'city' => fake()->city(),
                        'country' => fake()->country(),
                        'country_code' => fake()->countryCode(),
                        'zip' => fake()->postcode(),
                        'phone' => fake()->phoneNumber(),
                    ],
                ]
            );
        }
    }

    private function seedJohnDoeAddresses(int $customerId): void
    {
        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customerId, 'label' => 'Home'],
            [
                'is_default' => true,
                'address_json' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address1' => 'Hauptstrasse 1',
                    'city' => 'Berlin',
                    'province' => 'Berlin',
                    'province_code' => 'BE',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => '10115',
                    'phone' => '+49 30 12345678',
                ],
            ]
        );

        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customerId, 'label' => 'Work'],
            [
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
            ]
        );
    }

    private function seedJaneAddress(int $customerId): void
    {
        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customerId, 'label' => 'Home'],
            [
                'is_default' => true,
                'address_json' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'address1' => 'Schillerstrasse 45',
                    'city' => 'Munich',
                    'province' => 'Bavaria',
                    'province_code' => 'BY',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => '80336',
                ],
            ]
        );
    }
}
