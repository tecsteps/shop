<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        foreach ([
            ['email' => 'jane@example.com', 'name' => 'Jane Doe'],
            ['email' => 'john@example.com', 'name' => 'John Doe'],
        ] as $index => $data) {
            $customer = Customer::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'email' => $data['email'],
                ],
                [
                    'name' => $data['name'],
                    'password_hash' => null,
                    'marketing_opt_in' => $index === 0,
                ],
            );

            [$firstName, $lastName] = explode(' ', $data['name']);

            CustomerAddress::query()->updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'label' => 'Default',
                ],
                [
                    'address_json' => [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'company' => null,
                        'address1' => 'Musterstrasse 1',
                        'address2' => null,
                        'city' => 'Berlin',
                        'province' => 'Berlin',
                        'province_code' => 'BE',
                        'country' => 'Germany',
                        'country_code' => 'DE',
                        'postal_code' => '10115',
                        'phone' => null,
                    ],
                    'is_default' => true,
                ],
            );
        }
    }
}
