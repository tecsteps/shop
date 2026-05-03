<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Checkout>
 */
class CheckoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cart = Cart::factory()->create();

        return [
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => null,
            'status' => CheckoutStatus::Started,
            'email' => fake()->safeEmail(),
            'shipping_address_json' => null,
            'billing_address_json' => null,
            'shipping_method_id' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => [
                'subtotal' => 0,
                'discount' => 0,
                'shipping' => 0,
                'tax' => 0,
                'total' => 0,
                'currency' => $cart->currency,
            ],
            'expires_at' => now()->addDay(),
        ];
    }
}
