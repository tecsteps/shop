<?php

namespace Database\Factories;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'title' => Str::title($title),
            'handle' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'description_html' => '<p>'.fake()->sentence().'</p>',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Active->value,
        ];
    }

    /**
     * Indicate that the collection is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionStatus::Draft->value,
        ]);
    }
}
