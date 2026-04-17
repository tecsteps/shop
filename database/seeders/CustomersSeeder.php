<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomersSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'shop')->first();

        if ($store === null) {
            return;
        }

        $customers = [
            ['email' => 'amelia.young@example.com', 'name' => 'Amelia Young', 'city' => 'Austin', 'state' => 'TX'],
            ['email' => 'ben.carter@example.com', 'name' => 'Ben Carter', 'city' => 'Denver', 'state' => 'CO'],
            ['email' => 'cora.nguyen@example.com', 'name' => 'Cora Nguyen', 'city' => 'Seattle', 'state' => 'WA'],
            ['email' => 'dan.olsen@example.com', 'name' => 'Dan Olsen', 'city' => 'Chicago', 'state' => 'IL'],
            ['email' => 'eva.park@example.com', 'name' => 'Eva Park', 'city' => 'New York', 'state' => 'NY'],
            ['email' => 'finn.lee@example.com', 'name' => 'Finn Lee', 'city' => 'Miami', 'state' => 'FL'],
            ['email' => 'gia.sato@example.com', 'name' => 'Gia Sato', 'city' => 'Portland', 'state' => 'OR'],
            ['email' => 'hank.miller@example.com', 'name' => 'Hank Miller', 'city' => 'Boston', 'state' => 'MA'],
        ];

        foreach ($customers as $entry) {
            $customer = Customer::query()->firstOrCreate(
                ['store_id' => $store->getKey(), 'email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'password_hash' => Hash::make('password'),
                    'marketing_opt_in' => (bool) random_int(0, 1),
                    'email_verified_at' => now(),
                ],
            );

            CustomerAddress::query()->firstOrCreate(
                ['customer_id' => $customer->getKey(), 'label' => 'Home'],
                [
                    'address_json' => [
                        'first_name' => explode(' ', $entry['name'])[0],
                        'last_name' => explode(' ', $entry['name'])[1] ?? '',
                        'address1' => random_int(100, 999).' Main St',
                        'city' => $entry['city'],
                        'province_code' => $entry['state'],
                        'country_code' => 'US',
                        'postal_code' => (string) random_int(10_000, 99_999),
                    ],
                    'is_default' => 1,
                ],
            );
        }
    }
}
