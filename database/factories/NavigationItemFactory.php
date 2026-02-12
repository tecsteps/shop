<?php

namespace Database\Factories;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NavigationItem> */
class NavigationItemFactory extends Factory
{
    protected $model = NavigationItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'menu_id' => NavigationMenu::factory(),
            'type' => 'link',
            'label' => fake()->words(2, true),
            'url' => '/',
            'resource_id' => null,
            'position' => 0,
        ];
    }

    public function page(int $pageId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'page',
            'url' => null,
            'resource_id' => $pageId,
        ]);
    }

    public function collection(int $collectionId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'collection',
            'url' => null,
            'resource_id' => $collectionId,
        ]);
    }

    public function product(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
            'url' => null,
            'resource_id' => $productId,
        ]);
    }
}
