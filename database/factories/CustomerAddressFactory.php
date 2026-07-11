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
            'label' => 'Home',
            'address_json' => ['first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'address1' => fake()->streetAddress(), 'city' => fake()->city(), 'country' => 'DE', 'country_code' => 'DE', 'postal_code' => fake()->postcode()],
            'is_default' => true,
        ];
    }
}
