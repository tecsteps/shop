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
    /**
     * Seed customer accounts with addresses.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedAcmeFashion();
            $this->seedAcmeElectronics();
        });
    }

    private function seedAcmeFashion(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();

        /** @var array<int, array{email: string, name: string, marketing_opt_in: bool}> $customers */
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
            Customer::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password_hash' => Hash::make('password'),
                    'marketing_opt_in' => $data['marketing_opt_in'],
                ],
            );
        }

        $this->seedCustomerAddresses($store);
    }

    private function seedCustomerAddresses(Store $store): void
    {
        $john = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', 'customer@acme.test')
            ->firstOrFail();

        if ($john->addresses()->count() === 0) {
            CustomerAddress::create([
                'customer_id' => $john->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
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
                ],
            ]);

            CustomerAddress::create([
                'customer_id' => $john->id,
                'label' => 'Work',
                'is_default' => false,
                'address_json' => [
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
                ],
            ]);
        }

        $jane = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('email', 'jane@example.com')
            ->firstOrFail();

        if ($jane->addresses()->count() === 0) {
            CustomerAddress::create([
                'customer_id' => $jane->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
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
                ],
            ]);
        }

        $germanCities = [
            ['city' => 'Hamburg', 'zip' => '20095', 'street' => 'Moenckebergstrasse 7'],
            ['city' => 'Frankfurt', 'zip' => '60311', 'street' => 'Zeil 106'],
            ['city' => 'Cologne', 'zip' => '50667', 'street' => 'Hohe Strasse 52'],
            ['city' => 'Stuttgart', 'zip' => '70173', 'street' => 'Koenigstrasse 28'],
            ['city' => 'Dusseldorf', 'zip' => '40212', 'street' => 'Schadowstrasse 11'],
            ['city' => 'Dresden', 'zip' => '01067', 'street' => 'Prager Strasse 15'],
            ['city' => 'Leipzig', 'zip' => '04109', 'street' => 'Grimmaische Strasse 3'],
            ['city' => 'Nuremberg', 'zip' => '90402', 'street' => 'Breite Gasse 20'],
        ];

        $otherCustomers = Customer::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNotIn('email', ['customer@acme.test', 'jane@example.com'])
            ->orderBy('id')
            ->get();

        foreach ($otherCustomers as $index => $customer) {
            if ($customer->addresses()->count() > 0) {
                continue;
            }

            $cityData = $germanCities[$index % count($germanCities)];
            $nameParts = explode(' ', $customer->name);

            CustomerAddress::create([
                'customer_id' => $customer->id,
                'label' => 'Home',
                'is_default' => true,
                'address_json' => [
                    'first_name' => $nameParts[0] ?? '',
                    'last_name' => $nameParts[1] ?? '',
                    'company' => '',
                    'address1' => $cityData['street'],
                    'address2' => '',
                    'city' => $cityData['city'],
                    'province' => '',
                    'province_code' => '',
                    'country' => 'Germany',
                    'country_code' => 'DE',
                    'zip' => $cityData['zip'],
                    'phone' => '',
                ],
            ]);
        }
    }

    private function seedAcmeElectronics(): void
    {
        $store = Store::where('handle', 'acme-electronics')->firstOrFail();

        $customers = [
            ['email' => 'techfan@example.com', 'name' => 'Tech Fan'],
            ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover'],
        ];

        foreach ($customers as $data) {
            $customer = Customer::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password_hash' => Hash::make('password'),
                    'marketing_opt_in' => false,
                ],
            );

            if ($customer->addresses()->count() === 0) {
                $nameParts = explode(' ', $data['name']);
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'label' => 'Home',
                    'is_default' => true,
                    'address_json' => [
                        'first_name' => $nameParts[0] ?? '',
                        'last_name' => $nameParts[1] ?? '',
                        'company' => '',
                        'address1' => 'Alexanderplatz 1',
                        'address2' => '',
                        'city' => 'Berlin',
                        'province' => '',
                        'province_code' => '',
                        'country' => 'Germany',
                        'country_code' => 'DE',
                        'zip' => '10178',
                        'phone' => '',
                    ],
                ]);
            }
        }
    }
}
