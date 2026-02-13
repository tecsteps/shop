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
            'payment_method' => null,
            'email' => fake()->safeEmail(),
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => null,
            'expires_at' => null,
        ];
    }

    public function addressed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckoutStatus::Addressed,
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'province_code' => 'CA',
                'country_code' => 'US',
                'postal_code' => fake()->postcode(),
            ],
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckoutStatus::Completed,
        ]);
    }
}
