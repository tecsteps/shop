<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->randomElement(['Home', 'Work', 'Other']),
            'address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'company' => '',
                'address1' => fake()->streetAddress(),
                'address2' => '',
                'city' => fake()->city(),
                'province' => '',
                'province_code' => '',
                'country' => 'Germany',
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
                'phone' => fake()->phoneNumber(),
            ],
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }
}
