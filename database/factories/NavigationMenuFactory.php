<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NavigationMenu>
 */
class NavigationMenuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(2, true);

        return [
            'store_id' => Store::factory(),
            'handle' => Str::slug($title).'-'.Str::random(4),
            'title' => ucfirst($title),
        ];
    }
}
