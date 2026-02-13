<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SearchSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            SearchSettings::updateOrCreate(
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

            SearchSettings::updateOrCreate(
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
        });
    }
}
