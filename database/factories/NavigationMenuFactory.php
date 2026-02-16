<?php

namespace Database\Factories;

use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<NavigationMenu> */
class NavigationMenuFactory extends Factory
{
    protected $model = NavigationMenu::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Menu';

        return [
            'store_id' => Store::factory(),
            'name' => $name,
            'handle' => Str::slug($name),
        ];
    }
}
