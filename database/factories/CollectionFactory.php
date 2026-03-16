<?php

namespace Database\Factories;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title),
            'description_html' => '<p>'.fake()->paragraph().'</p>',
            'type' => 'manual',
            'status' => CollectionStatus::Active,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CollectionStatus::Draft,
        ]);
    }

    public function automated(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'automated',
        ]);
    }
}
