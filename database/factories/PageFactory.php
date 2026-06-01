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
        $title = fake()->unique()->sentence(3);

        return [
            'store_id' => Store::factory(),
            'title' => rtrim($title, '.'),
            'handle' => Str::slug($title),
            'body_html' => '<p>'.fake()->paragraph().'</p>',
            'status' => PageStatus::Published->value,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the page is a draft (not publicly visible).
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => PageStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the page is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => PageStatus::Archived->value,
        ]);
    }
}
