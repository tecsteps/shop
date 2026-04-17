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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => 'Home',
            'address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'province_code' => 'CA',
                'country_code' => 'US',
                'postal_code' => fake()->postcode(),
            ],
            'is_default' => 1,
        ];
    }
}
