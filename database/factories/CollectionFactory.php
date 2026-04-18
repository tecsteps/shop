<?php

namespace Database\Factories;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
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

    public function definition(): array
    {
        $title = $this->faker->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucwords($title),
            'handle' => Str::slug($title.'-'.$this->faker->unique()->numberBetween(1000, 99999)),
            'description_html' => '<p>'.$this->faker->paragraph().'</p>',
            'type' => CollectionType::Manual,
            'status' => CollectionStatus::Active,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => CollectionStatus::Draft]);
    }
}
