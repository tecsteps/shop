<?php

namespace Database\Factories;

use App\Enums\NavigationItemType;
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
            'type' => NavigationItemType::Link,
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->slug(1),
            'resource_id' => null,
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    public function forPage(int $pageId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NavigationItemType::Page,
            'url' => null,
            'resource_id' => $pageId,
        ]);
    }

    public function forCollection(int $collectionId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NavigationItemType::Collection,
            'url' => null,
            'resource_id' => $collectionId,
        ]);
    }

    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NavigationItemType::Product,
            'url' => null,
            'resource_id' => $productId,
        ]);
    }
}
