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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'title' => ucfirst($title),
            'handle' => Str::slug($title).'-'.Str::random(4),
            'description_html' => '<p>'.fake()->sentence().'</p>',
            'type' => CollectionType::Manual->value,
            'status' => CollectionStatus::Active->value,
        ];
    }

    public function automated(): self
    {
        return $this->state(fn (): array => ['type' => CollectionType::Automated->value]);
    }

    public function draft(): self
    {
        return $this->state(fn (): array => ['status' => CollectionStatus::Draft->value]);
    }
}
