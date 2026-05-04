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
            'parent_id' => null,
            'type' => NavigationItemType::Link,
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->slug(),
            'resource_id' => null,
            'position' => 0,
        ];
    }

    public function page(int $pageId): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NavigationItemType::Page,
            'url' => null,
            'resource_id' => $pageId,
        ]);
    }

    public function collection(int $collectionId): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NavigationItemType::Collection,
            'url' => null,
            'resource_id' => $collectionId,
        ]);
    }

    public function product(int $productId): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => NavigationItemType::Product,
            'url' => null,
            'resource_id' => $productId,
        ]);
    }
}
