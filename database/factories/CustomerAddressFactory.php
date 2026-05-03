<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->randomElement(['Home', 'Work', null]),
            'address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'company' => fake()->optional()->company(),
                'address1' => fake()->streetAddress(),
                'address2' => fake()->optional()->secondaryAddress(),
                'city' => fake()->city(),
                'province' => fake()->state(),
                'province_code' => fake()->stateAbbr(),
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => fake()->postcode(),
                'phone' => fake()->optional()->phoneNumber(),
            ],
            'is_default' => true,
        ];
    }
}
