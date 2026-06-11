<?php

namespace Database\Factories;

use App\Enums\NavigationItemType;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'type' => NavigationItemType::Link,
            'label' => Str::title(fake()->words(2, true)),
            'url' => '/',
            'resource_id' => null,
            'position' => 0,
        ];
    }

    /**
     * Indicate that the item links to a CMS page.
     */
    public function page(int $pageId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NavigationItemType::Page,
            'url' => null,
            'resource_id' => $pageId,
        ]);
    }

    /**
     * Indicate that the item links to a collection.
     */
    public function collection(int $collectionId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NavigationItemType::Collection,
            'url' => null,
            'resource_id' => $collectionId,
        ]);
    }

    /**
     * Indicate that the item links to a product.
     */
    public function product(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => NavigationItemType::Product,
            'url' => null,
            'resource_id' => $productId,
        ]);
    }
}
