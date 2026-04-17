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
            'status' => CheckoutStatus::Started,
            'email' => null,
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'totals_json' => null,
            'expires_at' => null,
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

    public function withShipping(): static
    {
        return $this->addressed()->state(fn (array $attributes) => [
            'status' => CheckoutStatus::ShippingSelected,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckoutStatus::Expired,
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fakeAddress(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address1' => fake()->streetAddress(),
            'address2' => null,
            'company' => null,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'province_code' => 'US-'.fake()->stateAbbr(),
            'country' => 'US',
            'postal_code' => fake()->postcode(),
            'phone' => null,
        ];
    }
}
