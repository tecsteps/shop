<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SearchSettings>
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
            'synonyms_json' => [],
            'stop_words_json' => [],
        ];
    }

    /**
     * Set the synonym groups (each a list of equivalent terms).
     *
     * @param  list<list<string>>  $groups
     */
    public function withSynonyms(array $groups): static
    {
        return $this->state(fn (array $attributes) => [
            'synonyms_json' => $groups,
        ]);
    }

    /**
     * Set the stop words.
     *
     * @param  list<string>  $words
     */
    public function withStopWords(array $words): static
    {
        return $this->state(fn (array $attributes) => [
            'stop_words_json' => $words,
        ]);
    }
}
