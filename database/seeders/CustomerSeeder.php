<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->customers() as $storeHandle => $customers) {
            $store = Store::query()->where('handle', $storeHandle)->firstOrFail();

            foreach ($customers as $customer) {
                Customer::withoutGlobalScopes()->updateOrCreate(
                    [
                        'store_id' => $store->getKey(),
                        'email' => $customer['email'],
                    ],
                    [
                        'name' => $customer['name'],
                        'password' => 'password',
                        'marketing_opt_in' => $customer['marketing_opt_in'],
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, list<array{email: string, name: string, marketing_opt_in: bool}>>
     */
    private function customers(): array
    {
        return [
            'acme-fashion' => [
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
            ],
            'acme-electronics' => [
                ['email' => 'techfan@example.com', 'name' => 'Tech Fan', 'marketing_opt_in' => true],
                ['email' => 'gadgetlover@example.com', 'name' => 'Gadget Lover', 'marketing_opt_in' => false],
            ],
        ];
    }
}
