<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\SearchQuery;
use App\Models\SearchSetting;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SearchAnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'demo-store')->firstOrFail();

        SearchSetting::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'synonyms_json' => [
                    ['tee', 't-shirt'],
                    ['sneaker', 'shoe'],
                ],
                'stop_words_json' => ['the', 'and', 'for'],
                'updated_at' => now(),
            ],
        );

        SearchQuery::query()->updateOrCreate(
            ['store_id' => $store->id, 'query' => 'demo t-shirt'],
            [
                'filters_json' => ['in_stock' => true],
                'results_count' => 1,
                'created_at' => now()->subMinutes(10),
            ],
        );

        AnalyticsEvent::query()->updateOrCreate(
            ['store_id' => $store->id, 'client_event_id' => 'evt-demo-001'],
            [
                'type' => 'product_view',
                'session_id' => 'session-demo-001',
                'customer_id' => null,
                'properties_json' => ['handle' => 'demo-t-shirt'],
                'occurred_at' => now()->subMinutes(15),
                'created_at' => now()->subMinutes(15),
            ],
        );

        DB::table('analytics_daily')->updateOrInsert(
            ['store_id' => $store->id, 'date' => now()->toDateString()],
            [
                'orders_count' => 1,
                'revenue_amount' => 5852,
                'aov_amount' => 5852,
                'visits_count' => 12,
                'add_to_cart_count' => 3,
                'checkout_started_count' => 2,
                'checkout_completed_count' => 1,
            ],
        );
    }
}
