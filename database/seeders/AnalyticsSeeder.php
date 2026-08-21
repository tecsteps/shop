<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Store::query()->whereIn('handle', ['acme-fashion', 'acme-electronics'])->get() as $store) {
            AnalyticsEvent::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $store->getKey(), 'client_event_id' => 'seed-'.$store->getKey().'-home'],
                ['type' => 'page_view', 'session_id' => 'seed-session-'.$store->getKey(), 'payload' => ['path' => '/'], 'properties_json' => ['path' => '/'], 'occurred_at' => now()->subDay()],
            );
        }
    }
}
