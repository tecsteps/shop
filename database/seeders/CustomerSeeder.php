<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Create the customer accounts with addresses (spec 07 §3.12).
     * All accounts use the password "password".
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

            $passwordHash = Hash::make('password');

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

            $customers = [];

            foreach ($fashionCustomers as [$email, $name, $marketingOptIn]) {
                // forceFill: store_id is not mass assignable (BelongsToStore).
                $customer = Customer::withoutGlobalScopes()
                    ->firstOrNew(['store_id' => $fashion->id, 'email' => $email]);

                $customer->forceFill([
                    'store_id' => $fashion->id,
                    'name' => $name,
                    'password_hash' => $passwordHash,
                    'marketing_opt_in' => $marketingOptIn,
                ])->save();

                $customers[$email] = $customer;
            }

            $electronicsCustomers = [
                ['techfan@example.com', 'Tech Fan'],
                ['gadgetlover@example.com', 'Gadget Lover'],
            ];

            foreach ($electronicsCustomers as [$email, $name]) {
                $customer = Customer::withoutGlobalScopes()
                    ->firstOrNew(['store_id' => $electronics->id, 'email' => $email]);

                $customer->forceFill([
                    'store_id' => $electronics->id,
                    'name' => $name,
                    'password_hash' => $passwordHash,
                    'marketing_opt_in' => false,
                ])->save();

                $customers[$email] = $customer;
            }

            $this->seedAddresses($customers);
        });
    }

    /**
     * Create the customers' addresses (spec 07 §3.12). The address JSON
     * follows the App\ValueObjects\Address shape used across the app.
     *
     * @param  array<string, Customer>  $customers
     */
    private function seedAddresses(array $customers): void
    {
        $addresses = [
            'customer@acme.test' => [
                ['Home', true, $this->address('John', 'Doe', 'Hauptstrasse 1', '10115', 'Berlin', phone: '+49 30 12345678')],
                ['Work', false, $this->address('John', 'Doe', 'Friedrichstrasse 100', '10117', 'Berlin', company: 'Acme Corp', address2: '3rd Floor', phone: '+49 30 87654321')],
            ],
            'jane@example.com' => [
                ['Home', true, $this->address('Jane', 'Smith', 'Schillerstrasse 45', '80336', 'Munich', province: 'Bavaria', provinceCode: 'BY')],
            ],
            'michael@example.com' => [
                ['Home', true, $this->address('Michael', 'Brown', 'Torstrasse 61', '10119', 'Berlin')],
            ],
            'sarah@example.com' => [
                ['Home', true, $this->address('Sarah', 'Wilson', 'Königsallee 27', '40212', 'Dusseldorf')],
            ],
            'david@example.com' => [
                ['Home', true, $this->address('David', 'Lee', 'Maximilianstrasse 12', '80539', 'Munich')],
            ],
            'emma@example.com' => [
                ['Home', true, $this->address('Emma', 'Garcia', 'Schildergasse 85', '50667', 'Cologne')],
            ],
            'james@example.com' => [
                ['Home', true, $this->address('James', 'Taylor', 'Zeil 106', '60313', 'Frankfurt')],
            ],
            'lisa@example.com' => [
                ['Home', true, $this->address('Lisa', 'Anderson', 'Mönckebergstrasse 7', '20095', 'Hamburg')],
            ],
            'robert@example.com' => [
                ['Home', true, $this->address('Robert', 'Martinez', 'Königsstrasse 40', '70173', 'Stuttgart')],
            ],
            'anna@example.com' => [
                ['Home', true, $this->address('Anna', 'Thomas', 'Petersstrasse 22', '04109', 'Leipzig')],
            ],
            'techfan@example.com' => [
                ['Home', true, $this->address('Tech', 'Fan', 'Einsteinstrasse 5', '81675', 'Munich')],
            ],
            'gadgetlover@example.com' => [
                ['Home', true, $this->address('Gadget', 'Lover', 'Linienstrasse 140', '10115', 'Berlin')],
            ],
        ];

        foreach ($addresses as $email => $customerAddresses) {
            foreach ($customerAddresses as [$label, $isDefault, $addressJson]) {
                $customers[$email]->addresses()->updateOrCreate(
                    ['label' => $label],
                    ['address_json' => $addressJson, 'is_default' => $isDefault],
                );
            }
        }
    }

    /**
     * Build one address JSON array (App\ValueObjects\Address shape).
     *
     * @return array<string, string|null>
     */
    private function address(
        string $firstName,
        string $lastName,
        string $address1,
        string $postalCode,
        string $city,
        ?string $company = null,
        ?string $address2 = null,
        ?string $province = null,
        ?string $provinceCode = null,
        ?string $phone = null,
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
            'postal_code' => $postalCode,
            'phone' => $phone,
        ];
    }
}
