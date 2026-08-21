<?php

namespace Database\Factories;

use App\Models\SearchSetting;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchSetting>
 */
class SearchSettingFactory extends Factory
{
    protected $model = SearchSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'synonyms' => [['tee', 't-shirt']],
            'stopwords' => ['the', 'a'],
            'synonyms_json' => [['tee', 't-shirt']],
            'stop_words_json' => ['the', 'a'],
            'enabled' => true,
        ];
    }
}
