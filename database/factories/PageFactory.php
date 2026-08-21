<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = '<h2>'.fake()->sentence().'</h2><p>'.fake()->paragraph().'</p><p>'.fake()->paragraph().'</p><p>'.fake()->paragraph().'</p>';

        return [
            'store_id' => Store::factory(),
            'title' => fake()->words(3, true),
            'handle' => fake()->unique()->slug(3),
            'body_html' => $body,
            'content' => $body,
            'status' => PageStatus::Published,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PageStatus::Draft, 'published_at' => null]);
    }

    public function archived(): static
    {
        return $this->state(['status' => PageStatus::Draft, 'published_at' => null]);
    }
}
