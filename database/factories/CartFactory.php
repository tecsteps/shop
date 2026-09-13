<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'currency' => 'USD',
            'cart_version' => 1,
            'status' => fake()->randomElement(CartStatus::cases()),
        ];
    }
}
