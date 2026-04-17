<?php

namespace Database\Factories;

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
            'status' => 'started',
        ];
    }

    public function addressed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'addressed',
            'email' => fake()->safeEmail(),
            'shipping_address_json' => json_encode($this->sampleAddress()),
            'billing_address_json' => json_encode($this->sampleAddress()),
        ]);
    }

    public function shippingSelected(): static
    {
        return $this->addressed()->state(fn (array $attributes) => [
            'status' => 'shipping_selected',
        ]);
    }

    public function paymentSelected(): static
    {
        return $this->shippingSelected()->state(fn (array $attributes) => [
            'status' => 'payment_selected',
            'payment_method' => 'credit_card',
            'expires_at' => now()->addHours(24)->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function sampleAddress(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => 'DE',
            'postal_code' => fake()->postcode(),
        ];
    }
}
