<?php

namespace Database\Factories;

use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NavigationMenu> */
class NavigationMenuFactory extends Factory
{
    protected $model = NavigationMenu::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'handle' => fake()->unique()->slug(2),
            'title' => fake()->words(2, true),
        ];
    }
}
