<?php

namespace Database\Factories;

use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchSettings>
 */
class SearchSettingsFactory extends Factory
{
    protected $model = SearchSettings::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'synonyms_json' => [],
            'stop_words_json' => [],
        ];
    }
}
