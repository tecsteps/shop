<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\NavigationMenu>
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
        return [
            'store_id' => Store::factory(),
            'handle' => fake()->unique()->slug(2),
            'title' => fake()->words(2, true),
        ];
    }

    /**
     * Indicate that the menu is the storefront main menu.
     */
    public function mainMenu(): static
    {
        return $this->state(fn (array $attributes) => [
            'handle' => 'main-menu',
            'title' => 'Main menu',
        ]);
    }

    /**
     * Indicate that the menu is the storefront footer menu.
     */
    public function footerMenu(): static
    {
        return $this->state(fn (array $attributes) => [
            'handle' => 'footer-menu',
            'title' => 'Footer menu',
        ]);
    }
}
