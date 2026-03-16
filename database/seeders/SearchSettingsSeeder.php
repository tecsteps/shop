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
        $stores = Store::all();

        foreach ($stores as $store) {
            SearchSettings::query()->withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->id],
                [
                    'synonyms_json' => [
                        'tee' => 'shirt t-shirt',
                        'pants' => 'trousers jeans',
                    ],
                    'stop_words_json' => ['the', 'a', 'an', 'and', 'or', 'but'],
                ],
            );

            app(SearchService::class)->rebuildIndex($store);
        }
    }
}
