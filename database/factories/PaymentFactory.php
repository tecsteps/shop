<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => OrderFactory::new(),
            'provider' => 'mock',
            'provider_payment_id' => 'mock_'.fake()->unique()->bothify('????????????????????'),
            'method' => PaymentMethod::CreditCard,
            'status' => PaymentStatus::Captured,
            'amount' => fake()->numberBetween(999, 99999),
            'currency' => 'EUR',
            'raw_json_encrypted' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => PaymentStatus::Pending]);
    }

    public function failed(): static
    {
        return $this->state(['status' => PaymentStatus::Failed]);
    }

    public function refunded(): static
    {
        return $this->state(['status' => PaymentStatus::Refunded]);
    }

    public function creditCard(): static
    {
        return $this->state(['method' => PaymentMethod::CreditCard]);
    }

    public function paypal(): static
    {
        return $this->state(['method' => PaymentMethod::Paypal]);
    }

    public function bankTransfer(): static
    {
        return $this->state(['method' => PaymentMethod::BankTransfer]);
    }
}
