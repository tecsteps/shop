<?php

namespace Database\Seeders;

use App\Models\SearchSetting;
use App\Models\Store;
use Illuminate\Database\Seeder;

class SearchSettingsSeeder extends Seeder
{
    public function run(): void
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

            SearchSetting::query()->updateOrCreate(
                ['store_id' => $store->getKey()],
                $values
            );
        }
    }
}
