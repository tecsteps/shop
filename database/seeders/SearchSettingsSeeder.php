<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SearchSettingsSeeder extends Seeder
{
    /**
     * Configure search synonyms and stop words (spec 07 §3.18).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $settingsByHandle = [
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

            foreach ($settingsByHandle as $handle => $settings) {
                $store = Store::query()->where('handle', $handle)->firstOrFail();

                SearchSettings::query()->updateOrCreate(
                    ['store_id' => $store->id],
                    $settings,
                );
            }
        });
    }
}
