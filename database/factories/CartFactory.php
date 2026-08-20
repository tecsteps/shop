<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = \App\Models\Cart::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'currency' => 'EUR', 'cart_version' => 1, 'status' => 'active'];
    }
}
