<?php

namespace Database\Factories;

use App\Enums\CartStatus;
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
            'discount_code' => null,
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CartStatus::Converted,
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CartStatus::Abandoned,
        ]);
    }
}
