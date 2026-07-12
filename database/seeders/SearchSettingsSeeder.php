<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Seeder;

class SearchSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'acme-fashion' => [[['tee', 't-shirt', 'tshirt'], ['pants', 'trousers', 'jeans'], ['sneakers', 'trainers', 'shoes'], ['hoodie', 'sweatshirt']], ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is']],
            'acme-electronics' => [[['laptop', 'notebook', 'computer'], ['headphones', 'earphones', 'earbuds'], ['cable', 'cord', 'wire']], ['the', 'a', 'an', 'and', 'or']],
        ] as $handle => [$synonyms, $stopWords]) {
            $store = Store::query()->where('handle', $handle)->firstOrFail();
            SearchSettings::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id], ['synonyms_json' => $synonyms, 'stop_words_json' => $stopWords]);
        }
    }
}
