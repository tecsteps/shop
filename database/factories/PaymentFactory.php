<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => 'credit_card',
            'status' => 'captured',
            'amount' => fake()->numberBetween(999, 99999),
            'currency' => 'EUR',
            'reference' => 'mock_'.Str::random(20),
            'captured_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'captured_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'captured_at' => null,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'credit_card',
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'paypal',
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'bank_transfer',
        ]);
    }
}
