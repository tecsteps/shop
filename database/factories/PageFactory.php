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
        $title = Str::title(fake()->unique()->words(3, true));

        $body = '<h2>'.fake()->sentence(4).'</h2>'
            .'<p>'.fake()->paragraph().'</p>'
            .'<p>'.fake()->paragraph().'</p>'
            .'<p>'.fake()->paragraph().'</p>';

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title),
            'body_html' => $body,
            'status' => PageStatus::Published,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the page is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::Draft,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the page is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::Archived,
        ]);
    }
}
