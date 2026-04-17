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
        DB::transaction(function () {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            // Acme Fashion customers
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

            foreach ($fashionCustomers as $data) {
                Customer::firstOrCreate(
                    ['store_id' => $fashion->id, 'email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => 'password',
                        'marketing_opt_in' => $data['marketing_opt_in'],
                    ],
                );
            }

            // Customer 1 addresses (John Doe)
            $john = Customer::where('store_id', $fashion->id)->where('email', 'customer@acme.test')->firstOrFail();
            $this->createAddress($john, [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => 'Hauptstrasse 1',
                'city' => 'Berlin',
                'postal_code' => '10115',
                'country_code' => 'DE',
                'phone' => '+49 30 12345678',
                'is_default' => true,
            ]);
            $this->createAddress($john, [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company' => 'Acme Corp',
                'address1' => 'Friedrichstrasse 100',
                'address2' => '3rd Floor',
                'city' => 'Berlin',
                'postal_code' => '10117',
                'country_code' => 'DE',
                'phone' => '+49 30 87654321',
                'is_default' => false,
            ]);

            // Customer 2 addresses (Jane Smith)
            $jane = Customer::where('store_id', $fashion->id)->where('email', 'jane@example.com')->firstOrFail();
            $this->createAddress($jane, [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'address1' => 'Schillerstrasse 45',
                'city' => 'Munich',
                'province_code' => 'BY',
                'postal_code' => '80336',
                'country_code' => 'DE',
                'is_default' => true,
            ]);

            // Customers 3-10: German addresses
            $germanAddresses = [
                ['Michael', 'Brown', 'Bahnhofstrasse 12', 'Hamburg', '20095'],
                ['Sarah', 'Wilson', 'Koenigstrasse 5', 'Stuttgart', '70173'],
                ['David', 'Lee', 'Marktplatz 8', 'Frankfurt', '60311'],
                ['Emma', 'Garcia', 'Schlossallee 22', 'Dresden', '01067'],
                ['James', 'Taylor', 'Lindenstrasse 15', 'Cologne', '50667'],
                ['Lisa', 'Anderson', 'Gartenweg 3', 'Dusseldorf', '40213'],
                ['Robert', 'Martinez', 'Bergstrasse 7', 'Leipzig', '04109'],
                ['Anna', 'Thomas', 'Kirchgasse 19', 'Nuremberg', '90402'],
            ];

            $remainingCustomers = Customer::where('store_id', $fashion->id)
                ->whereNotIn('email', ['customer@acme.test', 'jane@example.com'])
                ->orderBy('id')
                ->get();

            foreach ($remainingCustomers as $idx => $customer) {
                if (isset($germanAddresses[$idx])) {
                    $addr = $germanAddresses[$idx];
                    $this->createAddress($customer, [
                        'first_name' => $addr[0],
                        'last_name' => $addr[1],
                        'address1' => $addr[2],
                        'city' => $addr[3],
                        'postal_code' => $addr[4],
                        'country_code' => 'DE',
                        'is_default' => true,
                    ]);
                }
            }

            // Acme Electronics customers
            $elecCustomers = [
                ['email' => 'techfan@example.com', 'name' => 'Tech Fan'],
                ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover'],
            ];

            foreach ($elecCustomers as $data) {
                $customer = Customer::firstOrCreate(
                    ['store_id' => $electronics->id, 'email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => 'password',
                        'marketing_opt_in' => false,
                    ],
                );

                $names = explode(' ', $data['name']);
                $this->createAddress($customer, [
                    'first_name' => $names[0],
                    'last_name' => $names[1] ?? $names[0],
                    'address1' => 'Techstrasse '.rand(1, 100),
                    'city' => 'Berlin',
                    'postal_code' => '10115',
                    'country_code' => 'DE',
                    'is_default' => true,
                ]);
            }
        });
    }

    private function createAddress(Customer $customer, array $data): void
    {
        $defaults = [
            'company' => null,
            'address2' => null,
            'province_code' => null,
            'phone' => null,
            'is_default' => false,
        ];

        $data = array_merge($defaults, $data);

        // Use a combination that makes address unique enough
        CustomerAddress::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'address1' => $data['address1'],
            ],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'company' => $data['company'],
                'address2' => $data['address2'],
                'city' => $data['city'],
                'province_code' => $data['province_code'],
                'country_code' => $data['country_code'],
                'postal_code' => $data['postal_code'],
                'phone' => $data['phone'],
                'is_default' => $data['is_default'],
            ],
        );
    }
}
