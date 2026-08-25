<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    use SeedsDemoData;

    /**
     * Create customer accounts and addresses for every store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCustomers('acme-fashion', $this->fashionCustomers());
            $this->seedCustomers('acme-electronics', $this->electronicsCustomers());
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $customers
     */
    private function seedCustomers(string $storeHandle, array $customers): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        foreach ($customers as $data) {
            $customer = Customer::updateOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password_hash' => Hash::make('password'),
                    'marketing_opt_in' => $data['marketing_opt_in'],
                ],
            );

            foreach ($data['addresses'] as $address) {
                DB::table('customer_addresses')->updateOrInsert(
                    ['customer_id' => $customer->id, 'label' => $address['label']],
                    [
                        'address_json' => $address['address'],
                        'is_default' => $address['is_default'],
                    ],
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fashionCustomers(): array
    {
        $customers = [
            [
                'email' => 'customer@acme.test',
                'name' => 'John Doe',
                'marketing_opt_in' => true,
                'addresses' => [
                    [
                        'label' => 'Home',
                        'is_default' => true,
                        'address' => $this->demoAddress([
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                            'address1' => 'Hauptstrasse 1',
                            'city' => 'Berlin',
                            'postal_code' => '10115',
                            'phone' => '+49 30 12345678',
                        ]),
                    ],
                    [
                        'label' => 'Work',
                        'is_default' => false,
                        'address' => $this->demoAddress([
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                            'company' => 'Acme Corp',
                            'address1' => 'Friedrichstrasse 100',
                            'address2' => '3rd Floor',
                            'city' => 'Berlin',
                            'postal_code' => '10117',
                            'phone' => '+49 30 87654321',
                        ]),
                    ],
                ],
            ],
            [
                'email' => 'jane@example.com',
                'name' => 'Jane Smith',
                'marketing_opt_in' => false,
                'addresses' => [
                    [
                        'label' => 'Home',
                        'is_default' => true,
                        'address' => $this->demoAddress([
                            'first_name' => 'Jane',
                            'last_name' => 'Smith',
                            'address1' => 'Schillerstrasse 45',
                            'city' => 'Munich',
                            'province' => 'Bavaria',
                            'province_code' => 'BY',
                            'postal_code' => '80336',
                        ]),
                    ],
                ],
            ],
            ['email' => 'michael@example.com', 'name' => 'Michael Brown', 'marketing_opt_in' => true],
            ['email' => 'sarah@example.com', 'name' => 'Sarah Wilson', 'marketing_opt_in' => false],
            ['email' => 'david@example.com', 'name' => 'David Lee', 'marketing_opt_in' => true],
            ['email' => 'emma@example.com', 'name' => 'Emma Garcia', 'marketing_opt_in' => false],
            ['email' => 'james@example.com', 'name' => 'James Taylor', 'marketing_opt_in' => false],
            ['email' => 'lisa@example.com', 'name' => 'Lisa Anderson', 'marketing_opt_in' => true],
            ['email' => 'robert@example.com', 'name' => 'Robert Martinez', 'marketing_opt_in' => false],
            ['email' => 'anna@example.com', 'name' => 'Anna Thomas', 'marketing_opt_in' => true],
        ];

        // Customers 3-10 each get one default Faker-generated German address.
        foreach ($customers as $index => $customer) {
            if (! isset($customer['addresses'])) {
                $customers[$index]['addresses'] = [
                    [
                        'label' => 'Home',
                        'is_default' => true,
                        'address' => $this->fakerGermanAddress(),
                    ],
                ];
            }
        }

        return $customers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function electronicsCustomers(): array
    {
        return [
            [
                'email' => 'techfan@example.com',
                'name' => 'Tech Fan',
                'marketing_opt_in' => true,
                'addresses' => [
                    [
                        'label' => 'Home',
                        'is_default' => true,
                        'address' => $this->fakerGermanAddress(),
                    ],
                ],
            ],
            [
                'email' => 'gadgetlover@example.com',
                'name' => 'Gadget Lover',
                'marketing_opt_in' => false,
                'addresses' => [
                    [
                        'label' => 'Home',
                        'is_default' => true,
                        'address' => $this->fakerGermanAddress(),
                    ],
                ],
            ],
        ];
    }
}
