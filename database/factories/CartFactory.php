<?php

namespace Database\Factories;

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
            'store_id' => \App\Models\Store::factory(),
            'customer_id' => null,
            'currency' => 'EUR',
            'cart_version' => 1,
            'status' => 'active',
            'discount_code' => null,
        ];
    }
}
