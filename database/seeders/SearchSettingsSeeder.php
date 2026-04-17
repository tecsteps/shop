<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Database\Seeder;

class SearchSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        SearchSettings::query()->withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $fashion->id],
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

        SearchSettings::query()->withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $electronics->id],
            [
                'synonyms_json' => [
                    ['laptop', 'notebook', 'computer'],
                    ['headphones', 'earphones', 'earbuds'],
                    ['cable', 'cord', 'wire'],
                ],
                'stop_words_json' => ['the', 'a', 'an', 'and', 'or'],
            ],
        );

        $stores = Store::all();
        foreach ($stores as $store) {
            app(SearchService::class)->rebuildIndex($store);
        }
    }
}
