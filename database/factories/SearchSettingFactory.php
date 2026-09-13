<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'synonyms_json' => [['tv', 'television']],
            'stop_words_json' => ['the', 'and'],
        ];
    }
}
