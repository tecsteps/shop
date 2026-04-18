<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => 'Home',
            'address_json' => [
                'first_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'address1' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'country_code' => 'US',
                'zip' => $this->faker->postcode(),
            ],
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
