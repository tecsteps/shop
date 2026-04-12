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
            'currency' => 'EUR',
            'cart_version' => 1,
            'status' => CartStatus::Active->value,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CartStatus::Converted->value,
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CartStatus::Abandoned->value,
        ]);
    }
}
