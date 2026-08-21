<?php

namespace Database\Factories;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Checkout>
 */
class CheckoutFactory extends Factory
{
    protected $model = Checkout::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'cart_id' => CartFactory::new(),
            'customer_id' => null,
            'status' => CheckoutStatus::Started,
            'email' => fake()->safeEmail(),
            'shipping_address_json' => ['first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'address1' => fake()->streetAddress(), 'city' => fake()->city(), 'country_code' => 'DE', 'zip' => fake()->postcode()],
            'billing_address_json' => null,
            'shipping_rate_id' => null,
            'shipping_method_id' => null,
            'payment_method' => null,
            'discount_code' => null,
            'tax_provider_snapshot_json' => null,
            'totals_json' => null,
            'expires_at' => now()->addDay(),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => CheckoutStatus::Completed]);
    }

    public function expired(): static
    {
        return $this->state(['status' => CheckoutStatus::Expired, 'expires_at' => now()->subHour()]);
    }

    public function withCreditCard(): static
    {
        return $this->state(['payment_method' => 'credit_card']);
    }

    public function withPaypal(): static
    {
        return $this->state(['payment_method' => 'paypal']);
    }

    public function withBankTransfer(): static
    {
        return $this->state(['payment_method' => 'bank_transfer']);
    }
}
