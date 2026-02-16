<?php

namespace Database\Factories;

use App\Models\AppModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppModel> */
class AppModelFactory extends Factory
{
    protected $model = AppModel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'developer' => fake()->company(),
            'icon_url' => null,
            'is_public' => true,
        ];
    }
}
