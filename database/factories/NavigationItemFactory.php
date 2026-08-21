<?php

namespace Database\Factories;

use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    protected $model = NavigationItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'navigation_menu_id' => NavigationMenuFactory::new(),
            'menu_id' => null,
            'label' => fake()->words(2, true),
            'type' => 'link',
            'url' => '/',
            'resource_id' => null,
            'position' => 0,
            'parent_id' => null,
        ];
    }

    public function page(int $pageId): static
    {
        return $this->state(['type' => 'page', 'url' => null, 'resource_id' => $pageId]);
    }

    public function collection(int $collectionId): static
    {
        return $this->state(['type' => 'collection', 'url' => null, 'resource_id' => $collectionId]);
    }

    public function product(int $productId): static
    {
        return $this->state(['type' => 'product', 'url' => null, 'resource_id' => $productId]);
    }
}
