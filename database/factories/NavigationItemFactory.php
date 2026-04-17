<?php

namespace Database\Factories;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    protected $model = NavigationItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => NavigationMenu::factory(),
            'type' => 'link',
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->slug(2),
            'resource_id' => null,
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
