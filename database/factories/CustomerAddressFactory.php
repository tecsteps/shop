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
            'is_default' => true,
        ];
    }
}
