<?php

namespace Database\Factories;

use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NavigationMenu>
 */
class NavigationMenuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'handle' => Str::slug($title),
            'title' => $title,
        ];
    }

    public function mainMenu(): static
    {
        return $this->state(fn (array $attributes) => [
            'handle' => 'main-menu',
            'title' => 'Main Menu',
        ]);
    }

    public function footerMenu(): static
    {
        return $this->state(fn (array $attributes) => [
            'handle' => 'footer-menu',
            'title' => 'Footer Menu',
        ]);
    }
}
