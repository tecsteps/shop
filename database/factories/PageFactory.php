<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'store_id' => Store::factory(),
            'title' => Str::title($title),
            'handle' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'body_html' => '<h1>'.fake()->sentence().'</h1><p>'.fake()->paragraphs(3, true).'</p>',
            'status' => PageStatus::Published,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => PageStatus::Draft, 'published_at' => null]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => PageStatus::Archived]);
    }
}
