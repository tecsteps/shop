<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomerAddress>
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
            'label' => fake()->randomElement(['Home', 'Work']),
            'address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'company' => null,
                'address1' => fake()->streetAddress(),
                'address2' => null,
                'city' => fake()->city(),
                'province' => null,
                'province_code' => null,
                'country' => 'Germany',
                'country_code' => 'DE',
                'postal_code' => fake()->postcode(),
                'phone' => null,
            ],
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the address is the customer's default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
