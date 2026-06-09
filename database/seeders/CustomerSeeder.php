<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Seed the demo customers and their addresses (spec 07 section 3.12):
     * 10 Acme Fashion customers and 2 Acme Electronics customers for tenant
     * isolation testing.
     */
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
            $customer = $this->seedCustomer($fashion, $attributes);

            if (! $customer->addresses()->exists()) {
                $this->seedAddresses($customer);
            }
        }

        $electronicsCustomers = [
            ['email' => 'techfan@example.com', 'name' => 'Tech Fan', 'marketing_opt_in' => false],
            ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover', 'marketing_opt_in' => false],
        ];

        foreach ($electronicsCustomers as $attributes) {
            $customer = $this->seedCustomer($electronics, $attributes);

            if (! $customer->addresses()->exists()) {
                $customer->addresses()->create([
                    'label' => 'Home',
                    'address_json' => $this->fakerAddress($customer->name),
                    'is_default' => true,
                ]);
            }
        }
    }

    /**
     * @param  array{email: string, name: string, marketing_opt_in: bool}  $attributes
     */
    private function seedCustomer(Store $store, array $attributes): Customer
    {
        return Customer::query()->withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->getKey(), 'email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'marketing_opt_in' => $attributes['marketing_opt_in'],
                'password_hash' => Hash::make('password'),
            ],
        );
    }

    /**
     * Spec-defined addresses for customers 1 and 2; one Faker-generated
     * German default address for everyone else.
     */
    private function seedAddresses(Customer $customer): void
    {
        if ($customer->email === 'customer@acme.test') {
            $customer->addresses()->create([
                'label' => 'Home',
                'address_json' => $this->address('John', 'Doe', 'Hauptstrasse 1', 'Berlin', '10115', phone: '+49 30 12345678'),
                'is_default' => true,
            ]);
            $customer->addresses()->create([
                'label' => 'Work',
                'address_json' => $this->address('John', 'Doe', 'Friedrichstrasse 100', 'Berlin', '10117', company: 'Acme Corp', address2: '3rd Floor', phone: '+49 30 87654321'),
                'is_default' => false,
            ]);

            return;
        }

        if ($customer->email === 'jane@example.com') {
            $customer->addresses()->create([
                'label' => 'Home',
                'address_json' => $this->address('Jane', 'Smith', 'Schillerstrasse 45', 'Munich', '80336', province: 'Bavaria', provinceCode: 'BY'),
                'is_default' => true,
            ]);

            return;
        }

        $customer->addresses()->create([
            'label' => 'Home',
            'address_json' => $this->fakerAddress($customer->name),
            'is_default' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function address(
        string $firstName,
        string $lastName,
        string $address1,
        string $city,
        string $zip,
        string $company = '',
        string $address2 = '',
        string $province = '',
        string $provinceCode = '',
        string $phone = '',
    ): array {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => $company,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'province' => $province,
            'province_code' => $provinceCode,
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => $zip,
            'phone' => $phone,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function fakerAddress(?string $name): array
    {
        [$firstName, $lastName] = array_pad(explode(' ', (string) $name, 2), 2, '');

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => '',
            'address1' => fake()->streetAddress(),
            'address2' => '',
            'city' => fake()->city(),
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
