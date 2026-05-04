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
        foreach ($this->addresses() as $storeHandle => $addressesByEmail) {
            $store = Store::query()->where('handle', $storeHandle)->firstOrFail();

            foreach ($addressesByEmail as $email => $addresses) {
                $customer = Customer::withoutGlobalScopes()
                    ->where('store_id', $store->getKey())
                    ->where('email', $email)
                    ->firstOrFail();

                CustomerAddress::query()
                    ->where('customer_id', $customer->getKey())
                    ->update(['is_default' => false]);

                foreach ($addresses as $address) {
                    CustomerAddress::query()->updateOrCreate(
                        [
                            'customer_id' => $customer->getKey(),
                            'label' => $address['label'],
                        ],
                        [
                            'address_json' => $address['address_json'],
                            'is_default' => $address['is_default'],
                        ],
                    );
                }
            }
        }
    }

    /**
     * @return array<string, array<string, list<array{label: string, is_default: bool, address_json: array<string, string|null>}>>>
     */
    private function addresses(): array
    {
        return [
            'acme-fashion' => [
                'customer@acme.test' => [
                    [
                        'label' => 'Home',
                        'is_default' => true,
                        'address_json' => $this->address('John', 'Doe', 'Hauptstrasse 1', null, 'Berlin', null, '10115', '+49 30 12345678'),
                    ],
                    [
                        'label' => 'Work',
                        'is_default' => false,
                        'address_json' => $this->address('John', 'Doe', 'Friedrichstrasse 100', 'Acme Corp, 3rd Floor', 'Berlin', null, '10117', '+49 30 87654321'),
                    ],
                ],
                'jane@example.com' => [
                    [
                        'label' => 'Home',
                        'is_default' => true,
                        'address_json' => $this->address('Jane', 'Smith', 'Schillerstrasse 45', null, 'Munich', 'BY', '80336'),
                    ],
                ],
                'michael@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Michael', 'Brown', 'Kantstrasse 12', null, 'Berlin', null, '10623')],
                ],
                'sarah@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Sarah', 'Wilson', 'Lindenweg 8', null, 'Hamburg', null, '20095')],
                ],
                'david@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('David', 'Lee', 'Brueckenstrasse 22', null, 'Cologne', null, '50667')],
                ],
                'emma@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Emma', 'Garcia', 'Marktstrasse 5', null, 'Leipzig', null, '04109')],
                ],
                'james@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('James', 'Taylor', 'Rosenstrasse 17', null, 'Stuttgart', null, '70173')],
                ],
                'lisa@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Lisa', 'Anderson', 'Bahnhofstrasse 31', null, 'Dusseldorf', null, '40210')],
                ],
                'robert@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Robert', 'Martinez', 'Goethestrasse 9', null, 'Frankfurt', null, '60313')],
                ],
                'anna@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Anna', 'Thomas', 'Lessingstrasse 14', null, 'Nuremberg', null, '90402')],
                ],
            ],
            'acme-electronics' => [
                'techfan@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Tech', 'Fan', 'Silicon Allee 7', null, 'Berlin', null, '10119')],
                ],
                'gadgetlover@example.com' => [
                    ['label' => 'Home', 'is_default' => true, 'address_json' => $this->address('Gadget', 'Lover', 'Elektronstrasse 3', null, 'Munich', 'BY', '80331')],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function address(
        string $firstName,
        string $lastName,
        string $address1,
        ?string $address2,
        string $city,
        ?string $provinceCode,
        string $postalCode,
        ?string $phone = null,
    ): array {
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'province_code' => $provinceCode,
            'country' => 'DE',
            'postal_code' => $postalCode,
            'phone' => $phone,
        ];
    }
}
