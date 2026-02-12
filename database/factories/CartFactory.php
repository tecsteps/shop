<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\Customer;
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
            'currency' => 'EUR',
            'cart_version' => 1,
            'status' => CartStatus::Active,
        ];
    }

    public function forCustomer(): static
    {
        return $this->state(fn (): array => [
            'customer_id' => Customer::factory(),
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (): array => [
            'status' => CartStatus::Converted,
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (): array => [
            'status' => CartStatus::Abandoned,
        ]);
    }
}
