<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $fashionCustomers = [
                ['customer@acme.test', 'John Doe', true],
                ['jane@example.com', 'Jane Smith', false],
                ['michael@example.com', 'Michael Brown', true],
                ['sarah@example.com', 'Sarah Wilson', false],
                ['david@example.com', 'David Lee', true],
                ['emma@example.com', 'Emma Garcia', false],
                ['james@example.com', 'James Taylor', false],
                ['lisa@example.com', 'Lisa Anderson', true],
                ['robert@example.com', 'Robert Martinez', false],
                ['anna@example.com', 'Anna Thomas', true],
            ];

            foreach ($fashionCustomers as $index => [$email, $name, $marketingOptIn]) {
                $customer = $this->upsertCustomer($fashion->id, $email, $name, $marketingOptIn);
                [$firstName, $lastName] = explode(' ', $name, 2);

                $address = $this->address(
                    $firstName,
                    $lastName,
                    $index === 1 ? 'Schillerstrasse 45' : 'Hauptstrasse '.($index + 1),
                    $index === 1 ? 'Munich' : 'Berlin',
                    $index === 1 ? '80336' : sprintf('101%02d', 15 + $index),
                    $email === 'customer@acme.test' ? '+49 30 12345678' : '',
                );

                if ($email === 'jane@example.com') {
                    $address['province'] = 'Bavaria';
                    $address['province_code'] = 'BY';
                }

                CustomerAddress::query()->updateOrCreate(
                    ['customer_id' => $customer->id, 'label' => 'Home'],
                    ['address_json' => $address, 'is_default' => true],
                );

                if ($email === 'customer@acme.test') {
                    CustomerAddress::query()->updateOrCreate(
                        ['customer_id' => $customer->id, 'label' => 'Work'],
                        [
                            'address_json' => [
                                ...$this->address('John', 'Doe', 'Friedrichstrasse 100', 'Berlin', '10117', '+49 30 87654321'),
                                'company' => 'Acme Corp',
                                'address2' => '3rd Floor',
                            ],
                            'is_default' => false,
                        ],
                    );
                }
            }

            foreach ([
                ['techfan@example.com', 'Tech Fan'],
                ['gadgetlover@example.com', 'Gadget Lover'],
            ] as $index => [$email, $name]) {
                $customer = $this->upsertCustomer($electronics->id, $email, $name, false);
                [$firstName, $lastName] = explode(' ', $name, 2);

                CustomerAddress::query()->updateOrCreate(
                    ['customer_id' => $customer->id, 'label' => 'Home'],
                    [
                        'address_json' => $this->address($firstName, $lastName, 'Technikstrasse '.($index + 1), 'Berlin', '10115'),
                        'is_default' => true,
                    ],
                );
            }
        });
    }

    private function upsertCustomer(int $storeId, string $email, string $name, bool $marketingOptIn): Customer
    {
        return Customer::query()->updateOrCreate(
            ['store_id' => $storeId, 'email' => $email],
            ['password_hash' => 'password', 'name' => $name, 'marketing_opt_in' => $marketingOptIn],
        );
    }

    /**
     * @return array<string, string>
     */
    private function address(
        string $firstName,
        string $lastName,
        string $address,
        string $city,
        string $zip,
        string $phone = '',
    ): array {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'company' => '',
            'address1' => $address,
            'address2' => '',
            'city' => $city,
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => $zip,
            'phone' => $phone,
        ];
    }
}
