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
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $fashionCustomers = [
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

        foreach ($fashionCustomers as $attributes) {
            $this->seedCustomer($fashion, $attributes['email'], $attributes['name'], $attributes['marketing_opt_in']);
        }

        $this->seedCustomer($electronics, 'techfan@example.com', 'Tech Fan', false);
        $this->seedCustomer($electronics, 'gadgetlover@example.com', 'Gadget Lover', false);

        $this->seedAddress('customer@acme.test', 'Home', true, [
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
        ]);

        $this->seedAddress('customer@acme.test', 'Work', false, [
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
        ]);

        $this->seedAddress('jane@example.com', 'Home', true, [
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
        ]);

        foreach (['michael@example.com', 'sarah@example.com', 'david@example.com', 'emma@example.com', 'james@example.com', 'lisa@example.com', 'robert@example.com', 'anna@example.com', 'techfan@example.com', 'gadgetlover@example.com'] as $email) {
            $this->seedAddress($email, 'Home', true, $this->fakerAddress($email));
        }
    }

    protected function seedCustomer(Store $store, string $email, string $name, bool $marketingOptIn): void
    {
        Customer::query()->updateOrCreate(
            ['store_id' => $store->getKey(), 'email' => $email],
            [
                'name' => $name,
                'marketing_opt_in' => $marketingOptIn,
            ] + (Customer::query()->where('store_id', $store->getKey())->where('email', $email)->exists()
                ? []
                : ['password_hash' => 'password'])
        );
    }

    /**
     * @param  array<string, string>  $address
     */
    protected function seedAddress(string $email, string $label, bool $isDefault, array $address): void
    {
        $customer = Customer::query()->where('email', $email)->firstOrFail();

        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customer->getKey(), 'label' => $label],
            ['address_json' => $address, 'is_default' => $isDefault]
        );
    }

    /**
     * @return array<string, string>
     */
    protected function fakerAddress(string $email): array
    {
        $customer = Customer::query()->where('email', $email)->firstOrFail();
        $parts = explode(' ', (string) $customer->name, 2);

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
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
        ];
    }
}
