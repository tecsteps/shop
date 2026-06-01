<?php

namespace Database\Seeders;

use App\Enums\AppInstallationStatus;
use App\Enums\AppStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\SearchSetting;
use App\Models\WebhookSubscription;
use App\Services\SearchService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds Phase 8-10 demo data: default search settings, a full FTS index rebuild
 * for the demo store's catalog, and a sample installed app with an outbound
 * webhook subscription. Idempotent — safe to re-run.
 *
 * Runs after the catalog seeder so the products it indexes already exist.
 */
class SearchAndAppsSeeder extends Seeder
{
    public function __construct(private readonly SearchService $search) {}

    public function run(): void
    {
        $store = (new DemoStoreSeeder)->store();
        app()->instance('current_store', $store);

        // Default search settings (synonyms / stop words) for the demo store.
        SearchSetting::query()->updateOrCreate(
            ['store_id' => $store->id],
            ['synonyms_json' => [['tee', 't-shirt']], 'stop_words_json' => ['the', 'and']],
        );

        // Build the FTS index so storefront search returns results immediately.
        $this->search->reindexStore($store);

        // A sample first-party app installed on the demo store, subscribed to
        // order.created with a placeholder delivery endpoint.
        $app = App::query()->firstOrCreate(
            ['name' => 'Demo Analytics App'],
            ['status' => AppStatus::Active->value],
        );

        $installation = AppInstallation::query()->updateOrCreate(
            ['store_id' => $store->id, 'app_id' => $app->id],
            [
                'scopes_json' => ['read-products', 'read-orders'],
                'status' => AppInstallationStatus::Active->value,
                'installed_at' => now(),
            ],
        );

        WebhookSubscription::query()->firstOrCreate(
            ['store_id' => $store->id, 'app_installation_id' => $installation->id, 'event_type' => 'order.created'],
            [
                'target_url' => 'https://apps.example.test/webhooks/order-created',
                'signing_secret_encrypted' => 'whsec_'.Str::random(32),
                'status' => WebhookSubscriptionStatus::Active->value,
            ],
        );
    }
}
