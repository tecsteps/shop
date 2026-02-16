<?php

namespace Database\Factories;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Collection> */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        $title = fake()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'description_html' => '<p>'.fake()->sentence().'</p>',
            'type' => 'manual',
            'status' => CollectionStatus::Active,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => CollectionStatus::Draft]);
    }

    public function archived(): static
    {
        return $this->state(['status' => CollectionStatus::Archived]);
    }
}
