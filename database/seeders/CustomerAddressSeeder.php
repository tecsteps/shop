<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CustomerAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $customer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('email', 'customer@acme.test')
            ->firstOrFail();

        CustomerAddress::query()->updateOrCreate(
            [
                'customer_id' => $customer->getKey(),
                'label' => 'Home',
            ],
            [
                'address_json' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'address1' => 'Main Street 1',
                    'address2' => null,
                    'city' => 'Berlin',
                    'province_code' => null,
                    'country' => 'DE',
                    'postal_code' => '10115',
                ],
                'is_default' => true,
            ],
        );
    }
}
