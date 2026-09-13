<?php

namespace Database\Factories;

use App\Enums\NavigationItemType;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

class NavigationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'menu_id' => NavigationMenu::factory(),
            'type' => NavigationItemType::Link,
            'label' => fake()->words(2, true),
            'url' => fake()->url(),
            'resource_id' => null,
            'position' => fake()->numberBetween(0, 20),
        ];
    }
}
