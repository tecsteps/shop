<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchSettings>
 */
class SearchSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'synonyms_json' => [
                ['t-shirt', 'tee', 'tshirt'],
                ['sneakers', 'trainers'],
            ],
            'stop_words_json' => ['the', 'a', 'an'],
        ];
    }
}
