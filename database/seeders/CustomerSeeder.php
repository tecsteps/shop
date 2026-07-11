<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    private string $passwordHash;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->passwordHash = Hash::make('password');
            $fashion = Store::query()->where('handle', 'acme-fashion')->sole();
            $electronics = Store::query()->where('handle', 'acme-electronics')->sole();
            $fashionCustomers = [
                ['customer@acme.test', 'John Doe', true], ['jane@example.com', 'Jane Smith', false],
                ['michael@example.com', 'Michael Brown', true], ['sarah@example.com', 'Sarah Wilson', false],
                ['david@example.com', 'David Lee', true], ['emma@example.com', 'Emma Garcia', false],
                ['james@example.com', 'James Taylor', false], ['lisa@example.com', 'Lisa Anderson', true],
                ['robert@example.com', 'Robert Martinez', false], ['anna@example.com', 'Anna Thomas', true],
            ];
            foreach ($fashionCustomers as $index => [$email, $name, $marketingOptIn]) {
                $customer = $this->customer($fashion->id, $email, $name, $marketingOptIn);
                if ($email === 'customer@acme.test') {
                    $this->address($customer, 'Home', true, $this->addressData('John', 'Doe', 'Hauptstrasse 1', 'Berlin', '10115', '+49 30 12345678'));
                    $this->address($customer, 'Work', false, $this->addressData('John', 'Doe', 'Friedrichstrasse 100', 'Berlin', '10117', '+49 30 87654321', 'Acme Corp', '3rd Floor'));
                } elseif ($email === 'jane@example.com') {
                    $this->address($customer, 'Home', true, $this->addressData('Jane', 'Smith', 'Schillerstrasse 45', 'Munich', '80336', '', '', '', 'Bavaria', 'BY'));
                } else {
                    [$firstName, $lastName] = explode(' ', $name, 2);
                    $this->address($customer, 'Home', true, $this->addressData($firstName, $lastName, 'Musterstrasse '.($index + 10), 'Berlin', '101'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)));
                }
            }

            foreach ([['techfan@example.com', 'Tech Fan'], ['gadgetlover@example.com', 'Gadget Lover']] as $index => [$email, $name]) {
                $customer = $this->customer($electronics->id, $email, $name, false);
                [$firstName, $lastName] = explode(' ', $name, 2);
                $this->address($customer, 'Home', true, $this->addressData($firstName, $lastName, 'Technikstrasse '.($index + 1), 'Berlin', '1024'.($index + 3)));
            }
        });
    }

    private function customer(int $storeId, string $email, string $name, bool $marketingOptIn): Customer
    {
        return Customer::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $storeId, 'email' => $email],
            ['name' => $name, 'password_hash' => $this->passwordHash, 'marketing_opt_in' => $marketingOptIn],
        );
    }

    /** @param array<string, string> $address */
    private function address(Customer $customer, string $label, bool $isDefault, array $address): void
    {
        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'label' => $label],
            ['address_json' => $address, 'is_default' => $isDefault],
        );
    }

    /** @return array<string, string> */
    private function addressData(string $firstName, string $lastName, string $address1, string $city, string $zip, string $phone = '', string $company = '', string $address2 = '', string $province = '', string $provinceCode = ''): array
    {
        return [
            'first_name' => $firstName, 'last_name' => $lastName, 'company' => $company,
            'address1' => $address1, 'address2' => $address2, 'city' => $city,
            'province' => $province, 'province_code' => $provinceCode, 'country' => 'Germany',
            'country_code' => 'DE', 'zip' => $zip, 'phone' => $phone,
        ];
    }
}
