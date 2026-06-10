<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Database\Seeder;

class SearchSettingsSeeder extends Seeder
{
    /**
     * Seed search synonyms and stop words for both demo stores (spec 07
     * section 3.18), then rebuild each store's FTS5 index so seeded
     * products are immediately searchable.
     */
    public function run(SearchService $search): void
    {
        $settings = [
            'acme-fashion' => [
                'synonyms_json' => [
                    ['tee', 't-shirt', 'tshirt'],
                    ['pants', 'trousers', 'jeans'],
                    ['sneakers', 'trainers', 'shoes'],
                    ['hoodie', 'sweatshirt'],
                ],
                'stop_words_json' => ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is'],
            ],
            'acme-electronics' => [
                'synonyms_json' => [
                    ['laptop', 'notebook', 'computer'],
                    ['headphones', 'earphones', 'earbuds'],
                    ['cable', 'cord', 'wire'],
                ],
                'stop_words_json' => ['the', 'a', 'an', 'and', 'or'],
            ],
        ];

        foreach ($settings as $handle => $values) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();

            SearchSettings::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                $values,
            );

            $search->reindexStore($store);
        }
    }
}
