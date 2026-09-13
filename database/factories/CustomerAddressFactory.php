<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->randomElement(['Home', 'Work']),
            'address_json' => [
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
                'phone' => fake()->phoneNumber(),
            ],
            'is_default' => fake()->boolean(),
        ];
    }
}
