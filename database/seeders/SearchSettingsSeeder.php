<?php

namespace Database\Seeders;

use App\Models\SearchSetting;
use App\Models\Store;
use Illuminate\Database\Seeder;

class SearchSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get() as $store) {
            SearchSetting::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey()],
                ['enabled' => true, 'synonyms' => [], 'stopwords' => []],
            );
        }
    }
}
