<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'session_id' => fake()->uuid(),
            'status' => CartStatus::Active,
            'currency' => 'EUR',
            'cart_version' => 1,
            'discount_code' => null,
            'note' => null,
            'metadata' => null,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CartStatus::Converted,
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CartStatus::Abandoned,
        ]);
    }
}
