<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SearchSettingsSeeder extends Seeder
{
    /**
     * Configure search synonyms and stop words for every store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedSettings('acme-fashion', [
                'synonyms' => [
                    ['tee', 't-shirt', 'tshirt'],
                    ['pants', 'trousers', 'jeans'],
                    ['sneakers', 'trainers', 'shoes'],
                    ['hoodie', 'sweatshirt'],
                ],
                'stop_words' => ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is'],
            ]);

            $this->seedSettings('acme-electronics', [
                'synonyms' => [
                    ['laptop', 'notebook', 'computer'],
                    ['headphones', 'earphones', 'earbuds'],
                    ['cable', 'cord', 'wire'],
                ],
                'stop_words' => ['the', 'a', 'an', 'and', 'or'],
            ]);
        });
    }

    /**
     * @param  array{synonyms: array<int, array<int, string>>, stop_words: array<int, string>}  $settings
     */
    private function seedSettings(string $storeHandle, array $settings): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        SearchSettings::updateOrCreate(
            ['store_id' => $store->id],
            [
                'synonyms_json' => $settings['synonyms'],
                'stop_words_json' => $settings['stop_words'],
                'updated_at' => now(),
            ],
        );
    }
}
