<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'store_id' => Store::factory(),
            'title' => rtrim($title, '.'),
            'handle' => Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'body_html' => '<p>'.fake()->paragraph().'</p>',
            'status' => PageStatus::Published->value,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PageStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PageStatus::Archived->value,
        ]);
    }
}
