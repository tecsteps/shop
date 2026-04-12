<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<App>
 */
class AppFactory extends Factory
{
    protected $model = App::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'description' => fake()->sentence(),
            'scopes_json' => ['read_orders', 'write_orders'],
            'type' => fake()->randomElement(['first_party', 'third_party']),
        ];
    }
}
