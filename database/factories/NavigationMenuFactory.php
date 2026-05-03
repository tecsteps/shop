<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NavigationMenu>
 */
class NavigationMenuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->words(2, true));

        return [
            'store_id' => Store::factory(),
            'handle' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'title' => $title,
        ];
    }
}
