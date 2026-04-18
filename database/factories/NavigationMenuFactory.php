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

    public function definition(): array
    {
        $title = $this->faker->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'handle' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1000, 99999),
            'title' => ucfirst($title),
        ];
    }
}
