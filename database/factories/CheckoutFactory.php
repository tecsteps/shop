<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
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
            'email' => fake()->safeEmail(),
            'status' => CheckoutStatus::Started,
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'payment_method' => null,
            'totals_json' => null,
            'discount_code' => null,
            'note' => null,
            'expires_at' => null,
            'completed_at' => null,
            'metadata' => null,
        ];
    }

    public function addressed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Addressed,
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'province_code' => 'DE-BE',
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
            ],
        ]);
    }

    public function shippingSelected(): static
    {
        return $this->addressed()->state(fn (array $attributes) => [
            'status' => CheckoutStatus::ShippingSelected,
        ]);
    }

    public function paymentSelected(): static
    {
        return $this->shippingSelected()->state(fn (array $attributes) => [
            'status' => CheckoutStatus::PaymentSelected,
            'payment_method' => 'credit_card',
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    public function completed(): static
    {
        return $this->paymentSelected()->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Expired,
        ]);
    }
}
