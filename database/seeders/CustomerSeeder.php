<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        foreach ([
            [$fashion, 'jane@example.com', 'Jane Doe', true],
            [$fashion, 'john@example.com', 'John Doe', false],
            [$fashion, 'customer@acme.test', 'John Customer', true],
            [$fashion, 'maria@example.com', 'Maria Meyer', false],
            [$fashion, 'sam@example.com', 'Sam Taylor', false],
            [$fashion, 'li@example.com', 'Li Wei', false],
            [$fashion, 'fatima@example.com', 'Fatima Khan', false],
            [$fashion, 'noah@example.com', 'Noah Smith', false],
            [$fashion, 'emma@example.com', 'Emma Brown', false],
            [$fashion, 'olivia@example.com', 'Olivia Davis', false],
            [$electronics, 'techfan@example.com', 'Tech Fan', true],
            [$electronics, 'buyer@electronics.test', 'Electronics Buyer', false],
        ] as [$store, $email, $name, $canLogin]) {
            $customer = Customer::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'email' => $email,
                ],
                [
                    'name' => $name,
                    'password_hash' => $canLogin ? Hash::make('password') : null,
                    'marketing_opt_in' => $canLogin,
                ],
            );

            [$firstName, $lastName] = explode(' ', $name, 2);

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
