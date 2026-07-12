<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'mock',
            'method' => 'credit_card',
            'provider_payment_id' => 'mock_'.fake()->regexify('[A-Za-z0-9]{20}'),
            'status' => 'captured',
            'amount' => fake()->numberBetween(999, 99999),
            'currency' => 'EUR',
            'raw_json_encrypted' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'failed']);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'refunded']);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => ['method' => 'credit_card']);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => ['method' => 'paypal']);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => ['method' => 'bank_transfer']);
    }
}
