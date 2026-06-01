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
            'type' => NavigationItemType::Link->value,
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->slug(2),
            'resource_id' => null,
            'position' => 0,
        ];
    }

    /**
     * An item linking to a CMS page by id.
     */
    public function page(int $pageId): static
    {
        return $this->state(fn (): array => [
            'type' => NavigationItemType::Page->value,
            'url' => null,
            'resource_id' => $pageId,
        ]);
    }

    /**
     * An item linking to a collection by id.
     */
    public function collection(int $collectionId): static
    {
        return $this->state(fn (): array => [
            'type' => NavigationItemType::Collection->value,
            'url' => null,
            'resource_id' => $collectionId,
        ]);
    }

    /**
     * An item linking to a product by id.
     */
    public function product(int $productId): static
    {
        return $this->state(fn (): array => [
            'type' => NavigationItemType::Product->value,
            'url' => null,
            'resource_id' => $productId,
        ]);
    }
}
