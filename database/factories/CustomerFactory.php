<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'marketing_opt_in' => false,
        ];
    }

    public function withMarketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'marketing_opt_in' => true,
        ]);
    }
}
