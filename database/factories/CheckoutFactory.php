<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
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
            'shipping_address_json' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'country_code' => 'DE',
                'zip' => fake()->postcode(),
            ],
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => null,
            'expires_at' => now()->addDay(),
        ];
    }

    public function forCustomer(): static
    {
        return $this->state(fn (): array => [
            'customer_id' => Customer::factory(),
        ]);
    }
}
