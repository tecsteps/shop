<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fashionCustomers = [
            ['email' => 'customer@acme.test', 'first_name' => 'John', 'last_name' => 'Doe', 'marketing_opt_in' => true],
            ['email' => 'jane@example.com', 'first_name' => 'Jane', 'last_name' => 'Smith', 'marketing_opt_in' => false],
            ['email' => 'michael@example.com', 'first_name' => 'Michael', 'last_name' => 'Brown', 'marketing_opt_in' => true],
            ['email' => 'sarah@example.com', 'first_name' => 'Sarah', 'last_name' => 'Wilson', 'marketing_opt_in' => false],
            ['email' => 'david@example.com', 'first_name' => 'David', 'last_name' => 'Lee', 'marketing_opt_in' => true],
            ['email' => 'emma@example.com', 'first_name' => 'Emma', 'last_name' => 'Garcia', 'marketing_opt_in' => false],
            ['email' => 'james@example.com', 'first_name' => 'James', 'last_name' => 'Taylor', 'marketing_opt_in' => false],
            ['email' => 'lisa@example.com', 'first_name' => 'Lisa', 'last_name' => 'Anderson', 'marketing_opt_in' => true],
            ['email' => 'robert@example.com', 'first_name' => 'Robert', 'last_name' => 'Martinez', 'marketing_opt_in' => false],
            ['email' => 'anna@example.com', 'first_name' => 'Anna', 'last_name' => 'Thomas', 'marketing_opt_in' => true],
        ];
        $electronicsCustomers = [
            ['email' => 'techfan@example.com', 'first_name' => 'Tech', 'last_name' => 'Fan', 'marketing_opt_in' => false],
            ['email' => 'gadgetlover@example.com', 'first_name' => 'Gadget', 'last_name' => 'Lover', 'marketing_opt_in' => true],
        ];

        foreach ($fashionCustomers as $position => $customerData) {
            $customer = $this->seedCustomer('acme-fashion', $customerData);
            $this->seedAddresses($customer, $position);
        }

        foreach ($electronicsCustomers as $position => $customerData) {
            $customer = $this->seedCustomer('acme-electronics', $customerData);
            $this->seedAddresses($customer, $position + 10);
        }
    }

    private function seedCustomer(string $storeHandle, array $customerData): Customer
    {
        $storeId = \App\Models\Store::query()->where('handle', $storeHandle)->value('id');
        $password = Hash::make('password');

        return Customer::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $storeId, 'email' => $customerData['email']],
            ['first_name' => $customerData['first_name'], 'last_name' => $customerData['last_name'], 'password_hash' => $password, 'status' => 'active', 'email_verified_at' => now(), 'metadata' => ['marketing_opt_in' => $customerData['marketing_opt_in']]],
        );
    }

    private function seedAddresses(Customer $customer, int $position): void
    {
        $addresses = match ($customer->email) {
            'customer@acme.test' => [
                ['label' => 'Home', 'is_default' => true, 'address' => $this->address('John', 'Doe', 'Hauptstrasse 1', 'Berlin', '10115', '+49 30 12345678')],
                ['label' => 'Work', 'is_default' => false, 'address' => $this->address('John', 'Doe', 'Friedrichstrasse 100, 3rd Floor', 'Berlin', '10117', '+49 30 87654321', 'Acme Corp')],
            ],
            'jane@example.com' => [['label' => 'Home', 'is_default' => true, 'address' => $this->address('Jane', 'Smith', 'Schillerstrasse 45', 'Munich', '80336', '', '', 'Bavaria', 'BY')]],
            default => [['label' => 'Home', 'is_default' => true, 'address' => $this->address($customer->first_name, $customer->last_name, $position < 10 ? 'Hauptstrasse '.($position + 10) : 'Teststrasse '.($position + 1), $position % 2 === 0 ? 'Berlin' : 'Hamburg', $position < 10 ? '10115' : '20095', '')]],
        };

        foreach ($addresses as $address) {
            $customer->addresses()->updateOrCreate(
                ['label' => $address['label']],
                ['address_json' => $address['address'], 'is_default' => $address['is_default']],
            );
        }
    }

    private function address(string $firstName, string $lastName, string $address1, string $city, string $zip, string $phone, string $company = '', string $province = '', string $provinceCode = ''): array
    {
        return ['first_name' => $firstName, 'last_name' => $lastName, 'company' => $company, 'address1' => $address1, 'address2' => '', 'city' => $city, 'province' => $province, 'province_code' => $provinceCode, 'country' => 'Germany', 'country_code' => 'DE', 'zip' => $zip, 'phone' => $phone];
    }
}
