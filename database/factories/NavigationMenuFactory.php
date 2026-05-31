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
        $title = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'handle' => Str::slug($title),
            'title' => Str::title($title),
        ];
    }

    /**
     * The canonical main navigation menu.
     */
    public function mainMenu(): static
    {
        return $this->state(fn (): array => [
            'handle' => 'main-menu',
            'title' => 'Main menu',
        ]);
    }

    /**
     * The canonical footer navigation menu.
     */
    public function footerMenu(): static
    {
        return $this->state(fn (): array => [
            'handle' => 'footer-menu',
            'title' => 'Footer menu',
        ]);
    }
}
