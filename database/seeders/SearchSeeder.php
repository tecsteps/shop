<?php

namespace Database\Seeders;

use App\Models\SearchQuery;
use App\Models\SearchSetting;
use App\Models\Store;
use App\Services\SearchService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class SearchSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::query()
            ->whereIn('handle', ['acme-fashion', 'acme-electronics'])
            ->get()
            ->keyBy('handle');

        if ($stores->isEmpty()) {
            return;
        }

        $timestamp = CarbonImmutable::parse('2026-02-11 09:00:00', 'UTC');

        foreach ($stores as $handle => $store) {
            SearchSetting::query()->updateOrCreate(
                ['store_id' => $store->id],
                [
                    'synonyms_json' => $handle === 'acme-fashion'
                        ? [['tee', 't-shirt', 'shirt'], ['jeans', 'denim']]
                        : [['tv', 'television'], ['phone', 'smartphone']],
                    'stop_words_json' => ['and', 'the', 'for', 'with'],
                    'updated_at' => $timestamp,
                ],
            );
        }

        app(SearchService::class)->reindex();

        SearchQuery::query()->whereIn('store_id', $stores->pluck('id'))->delete();

        $rows = [
            [
                'store_id' => $stores->get('acme-fashion')?->id,
                'query' => 'cotton shirt',
                'filters_json' => ['vendor' => 'Acme Basics'],
                'results_count' => 1,
                'created_at' => CarbonImmutable::parse('2026-02-10 10:00:00', 'UTC'),
            ],
            [
                'store_id' => $stores->get('acme-fashion')?->id,
                'query' => 'denim jacket',
                'filters_json' => ['product_type' => 'Jackets'],
                'results_count' => 1,
                'created_at' => CarbonImmutable::parse('2026-02-10 10:15:00', 'UTC'),
            ],
            [
                'store_id' => $stores->get('acme-electronics')?->id,
                'query' => 'wireless earbuds',
                'filters_json' => null,
                'results_count' => 1,
                'created_at' => CarbonImmutable::parse('2026-02-10 11:00:00', 'UTC'),
            ],
            [
                'store_id' => $stores->get('acme-electronics')?->id,
                'query' => '4k tv',
                'filters_json' => ['vendor' => 'Acme Home'],
                'results_count' => 1,
                'created_at' => CarbonImmutable::parse('2026-02-10 11:10:00', 'UTC'),
            ],
        ];

        foreach ($rows as $row) {
            if (! $row['store_id']) {
                continue;
            }

            SearchQuery::query()->create($row);
        }
    }
}
