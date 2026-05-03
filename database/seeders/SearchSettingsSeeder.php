<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Database\Seeder;

class SearchSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        SearchSettings::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'synonyms_json' => [
                    ['tee', 't-shirt', 'tshirt'],
                    ['pants', 'trousers', 'jeans'],
                    ['sneakers', 'trainers', 'shoes'],
                    ['hoodie', 'sweatshirt'],
                ],
                'stop_words_json' => ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is'],
            ],
        );

        app(SearchService::class)->reindexStore($store);
    }
}
