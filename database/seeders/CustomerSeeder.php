<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        $fashionCustomers = [
            ['customer@acme.test', 'John Doe', true], ['jane@example.com', 'Jane Smith', false],
            ['michael@example.com', 'Michael Brown', true], ['sarah@example.com', 'Sarah Wilson', false],
            ['david@example.com', 'David Lee', true], ['emma@example.com', 'Emma Garcia', false],
            ['james@example.com', 'James Taylor', false], ['lisa@example.com', 'Lisa Anderson', true],
            ['robert@example.com', 'Robert Martinez', false], ['anna@example.com', 'Anna Thomas', true],
        ];
        foreach ($fashionCustomers as $index => [$email, $name, $marketing]) {
            $customer = $this->customer($fashion, $email, $name, $marketing);
            $this->address($customer, 'Home', true, [
                'first_name' => str($name)->before(' ')->toString(), 'last_name' => str($name)->after(' ')->toString(),
                'company' => null, 'address1' => $index === 0 ? 'Hauptstrasse 1' : ($index === 1 ? 'Schillerstrasse 45' : 'Musterstrasse '.($index + 10)),
                'address2' => null, 'city' => $index === 1 ? 'Munich' : 'Berlin', 'province' => $index === 1 ? 'Bavaria' : 'Berlin',
                'province_code' => $index === 1 ? 'BY' : 'BE', 'country' => 'DE', 'country_code' => 'DE',
                'postal_code' => $index === 1 ? '80336' : '10115', 'zip' => $index === 1 ? '80336' : '10115',
                'phone' => $index === 0 ? '+49 30 12345678' : null,
            ]);
            if ($index === 0) {
                $this->address($customer, 'Work', false, [
                    'first_name' => 'John', 'last_name' => 'Doe', 'company' => 'Acme Corp', 'address1' => 'Friedrichstrasse 100',
                    'address2' => '3rd Floor', 'city' => 'Berlin', 'province' => 'Berlin', 'province_code' => 'BE',
                    'country' => 'DE', 'country_code' => 'DE', 'postal_code' => '10117', 'zip' => '10117', 'phone' => '+49 30 87654321',
                ]);
            }
        }

        foreach ([['techfan@example.com', 'Tech Fan'], ['gadgetlover@example.com', 'Gadget Lover']] as $index => [$email, $name]) {
            $customer = $this->customer($electronics, $email, $name, false);
            $this->address($customer, 'Home', true, [
                'first_name' => str($name)->before(' ')->toString(), 'last_name' => str($name)->after(' ')->toString(),
                'address1' => 'Technikweg '.($index + 1), 'city' => 'Berlin', 'province' => 'Berlin', 'province_code' => 'BE',
                'country' => 'DE', 'country_code' => 'DE', 'postal_code' => '10115', 'zip' => '10115',
            ]);
        }
    }

    private function customer(Store $store, string $email, string $name, bool $marketing): Customer
    {
        return Customer::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'email' => $email], [
            'name' => $name, 'password_hash' => Hash::make('password'), 'marketing_opt_in' => $marketing,
        ]);
    }

    /** @param array<string, mixed> $address */
    private function address(Customer $customer, string $label, bool $default, array $address): void
    {
        $customer->addresses()->updateOrCreate(['label' => $label], ['address_json' => $address, 'is_default' => $default]);
    }
}
