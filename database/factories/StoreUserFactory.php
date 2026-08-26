<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreUser>
 */
class StoreUserFactory extends Factory
{
    protected $model = StoreUser::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'role' => 'owner',
        ];
    }
}
