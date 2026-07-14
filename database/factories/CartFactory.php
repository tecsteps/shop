<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'currency' => 'EUR',
            'cart_version' => 1,
            'status' => 'active',
        ];
    }

    public function forCustomer(?Customer $customer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer?->getKey() ?? Customer::factory(),
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'converted']);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'abandoned']);
    }
}
