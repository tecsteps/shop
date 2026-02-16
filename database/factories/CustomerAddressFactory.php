<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerAddress> */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => null,
            'address1' => fake()->streetAddress(),
            'address2' => null,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'province_code' => fake()->stateAbbr(),
            'country' => 'United States',
            'country_code' => 'US',
            'zip' => fake()->postcode(),
            'phone' => null,
            'is_default' => false,
        ];
    }
}
