<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checkout>
 */
class CheckoutFactory extends Factory
{
    protected $model = Checkout::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'cart_id' => Cart::factory(),
            'customer_id' => null,
            'status' => CheckoutStatus::Started,
        ];
    }

    public function addressed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Addressed,
            'email' => fake()->safeEmail(),
            'shipping_address_json' => $this->fakeAddress(),
            'billing_address_json' => $this->fakeAddress(),
        ]);
    }

    public function shippingSelected(): static
    {
        return $this->addressed()->state(fn (array $attributes) => [
            'status' => CheckoutStatus::ShippingSelected,
            'shipping_method_id' => 1,
        ]);
    }

    public function paymentSelected(): static
    {
        return $this->shippingSelected()->state(fn (array $attributes) => [
            'status' => CheckoutStatus::PaymentSelected,
            'payment_method' => PaymentMethod::CreditCard,
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function completed(): static
    {
        return $this->paymentSelected()->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Completed,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Expired,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function fakeAddress(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => 'US',
            'postal_code' => fake()->postcode(),
        ];
    }
}
