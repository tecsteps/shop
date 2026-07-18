<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchSettingsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('search_settings')) {
            return;
        }

        DB::transaction(function (): void {
            foreach ([
                'acme-fashion' => [
                    'synonyms' => [['tee', 't-shirt', 'tshirt'], ['pants', 'trousers', 'jeans'], ['sneakers', 'trainers', 'shoes'], ['hoodie', 'sweatshirt']],
                    'stop_words' => ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is'],
                ],
                'acme-electronics' => [
                    'synonyms' => [['laptop', 'notebook', 'computer'], ['headphones', 'earphones', 'earbuds'], ['cable', 'cord', 'wire']],
                    'stop_words' => ['the', 'a', 'an', 'and', 'or'],
                ],
            ] as $handle => $settings) {
                $store = Store::query()->where('handle', $handle)->firstOrFail();

                DB::table('search_settings')->updateOrInsert(
                    ['store_id' => $store->id],
                    [
                        'synonyms_json' => json_encode($settings['synonyms'], JSON_THROW_ON_ERROR),
                        'stop_words_json' => json_encode($settings['stop_words'], JSON_THROW_ON_ERROR),
                    ],
                );
            }
        });
    }
}
