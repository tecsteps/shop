<?php

namespace Database\Factories;

use App\Enums\NavigationItemType;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => NavigationMenu::factory(),
            'type' => NavigationItemType::Link,
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->slug(),
            'resource_id' => null,
            'position' => 0,
        ];
    }
}
