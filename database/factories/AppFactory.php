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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'icon_url' => null,
            'developer_name' => fake()->company(),
            'is_listed' => true,
        ];
    }

    /**
     * Indicate that the app is unlisted.
     */
    public function unlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_listed' => false,
        ]);
    }
}
