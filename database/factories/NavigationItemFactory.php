<?php

namespace Database\Factories;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NavigationItem> */
class NavigationItemFactory extends Factory
{
    protected $model = NavigationItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => NavigationMenu::factory(),
            'parent_id' => null,
            'title' => fake()->words(2, true),
            'type' => NavigationItemType::Link,
            'url' => '/'.fake()->slug(2),
            'resource_id' => null,
            'position' => 0,
        ];
    }
}
