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

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'store_id' => Store::factory(),
            'title' => $title,
            'handle' => Str::slug($title),
            'body_html' => '<p>'.$this->faker->paragraph().'</p>',
            'status' => PageStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => PageStatus::Published,
            'published_at' => now(),
        ]);
    }
}
