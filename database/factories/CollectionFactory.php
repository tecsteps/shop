<?php

namespace Database\Factories;

use App\Enums\CollectionStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    protected $model = \App\Models\Collection::class;

    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'title' => fake()->words(2, true), 'handle' => fake()->unique()->slug(2), 'description' => '<p>'.fake()->sentence().'</p>', 'status' => CollectionStatus::Active, 'image_url' => null];
    }

    public function draft(): static
    {
        return $this->state(['status' => CollectionStatus::Draft]);
    }

    public function archived(): static
    {
        return $this->state(['status' => CollectionStatus::Archived]);
    }

    public function automated(): static
    {
        return $this->state(['description' => '<p>Automatically populated collection.</p>']);
    }
}
