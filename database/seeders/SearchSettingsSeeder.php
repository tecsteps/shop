<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Seeder;

class SearchSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()
            ->get()
            ->each(function (Store $store): void {
                SearchSettings::withoutGlobalScopes()->updateOrCreate(
                    ['store_id' => $store->getKey()],
                    [
                        'synonyms_json' => [
                            ['t-shirt', 'tee', 'tshirt'],
                            ['sneakers', 'trainers', 'kicks'],
                        ],
                        'stop_words_json' => ['the', 'a', 'an', 'is', 'are'],
                    ],
                );
            });
    }
}
