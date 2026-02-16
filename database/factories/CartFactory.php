<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cart> */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'session_id' => fake()->sha1(),
            'status' => CartStatus::Active,
            'currency' => 'USD',
            'cart_version' => 1,
            'note' => null,
        ];
    }

    public function converted(): static
    {
        return $this->state(['status' => CartStatus::Converted]);
    }

    public function abandoned(): static
    {
        return $this->state(['status' => CartStatus::Abandoned]);
    }
}
