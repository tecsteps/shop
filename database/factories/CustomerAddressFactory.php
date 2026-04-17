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
            'label' => fake()->randomElement(['Home', 'Work', 'Other']),
            'address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'company' => fake()->optional()->company(),
                'address1' => fake()->streetAddress(),
                'address2' => fake()->optional()->secondaryAddress(),
                'city' => fake()->city(),
                'province' => fake()->stateAbbr(),
                'province_code' => fake()->stateAbbr(),
                'country' => 'United States',
                'country_code' => 'US',
                'zip' => fake()->postcode(),
                'phone' => fake()->optional()->phoneNumber(),
            ],
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
