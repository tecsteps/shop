<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => fake()->randomElement(['Home', 'Work', 'Other']),
            'address_json' => ['first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'company' => '', 'address1' => fake()->streetAddress(), 'address2' => '', 'city' => fake()->city(), 'province' => '', 'province_code' => '', 'country' => 'Germany', 'country_code' => 'DE', 'zip' => fake()->postcode(), 'phone' => ''],
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
