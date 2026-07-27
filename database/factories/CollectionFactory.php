<?php

namespace Database\Factories;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Collection>
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
        return [
            'store_id' => Store::factory(),
            'title' => fake()->unique()->words(2, true),
            'handle' => fake()->unique()->slug(2),
            'description_html' => null,
            'type' => CollectionType::Manual,
            'status' => CollectionStatus::Active,
        ];
    }

    /**
     * Indicate that the collection is rule-based.
     */
    public function automated(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CollectionType::Automated,
        ]);
    }

    /**
     * Indicate that the collection is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionStatus::Draft,
        ]);
    }
}
