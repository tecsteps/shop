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
    protected $model = NavigationMenu::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->randomElement(['Main Menu', 'Footer Menu', 'Mobile Menu']);

        return [
            'store_id' => Store::factory(),
            'handle' => Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'title' => $title,
        ];
    }
}
