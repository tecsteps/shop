<?php

namespace Database\Factories;

use App\Enums\AppStatus;
use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<App>
 */
class AppFactory extends Factory
{
    protected $model = App::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' App',
            'status' => AppStatus::Active,
            'created_at' => now(),
        ];
    }
}
