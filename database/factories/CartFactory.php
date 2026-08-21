<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = \App\Models\Cart::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'customer_id' => null, 'currency' => 'EUR', 'cart_version' => 1, 'status' => CartStatus::Active, 'discount_code' => null];
    }

    public function forCustomer(): static
    {
        return $this->state(['customer_id' => Customer::factory()]);
    }

    public function converted(): static
    {
        return $this->state(['status' => CartStatus::Converted]);
    }

    public function abandoned(): static
    {
        return $this->state(['status' => CartStatus::Abandoned]);
    }
}
