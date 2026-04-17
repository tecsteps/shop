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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucfirst($title),
            'handle' => Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'description_html' => '<p>'.fake()->sentence().'</p>',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Active->value,
        ];
    }
}
