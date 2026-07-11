<?php

namespace Database\Seeders;

use App\Models\SearchSettings;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SearchSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $data = [
                'acme-fashion' => [
                    [['tee', 't-shirt', 'tshirt'], ['pants', 'trousers', 'jeans'], ['sneakers', 'trainers', 'shoes'], ['hoodie', 'sweatshirt']],
                    ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is'],
                ],
                'acme-electronics' => [
                    [['laptop', 'notebook', 'computer'], ['headphones', 'earphones', 'earbuds'], ['cable', 'cord', 'wire']],
                    ['the', 'a', 'an', 'and', 'or'],
                ],
            ];
            foreach ($data as $handle => [$synonyms, $stopWords]) {
                $store = Store::query()->where('handle', $handle)->sole();
                SearchSettings::withoutGlobalScopes()->updateOrCreate(
                    ['store_id' => $store->id],
                    ['synonyms_json' => $synonyms, 'stop_words_json' => $stopWords],
                );
            }
        });
    }
}
