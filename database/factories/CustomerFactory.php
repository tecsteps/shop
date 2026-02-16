<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'accepts_marketing' => false,
        ];
    }
}
