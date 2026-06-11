<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'currency' => 'EUR',
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ];
    }

    /**
     * Attach the cart to a new customer.
     */
    public function forCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }

    /**
     * Mark the cart as converted to an order.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CartStatus::Converted,
        ]);
    }

    /**
     * Mark the cart as abandoned.
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CartStatus::Abandoned,
        ]);
    }
}
