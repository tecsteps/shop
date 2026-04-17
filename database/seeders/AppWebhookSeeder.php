<?php

namespace Database\Seeders;

use App\Enums\AppInstallationStatus;
use App\Enums\AppStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\OauthClient;
use App\Models\OauthToken;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Database\Seeder;

class AppWebhookSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'demo-store')->firstOrFail();

        $app = App::query()->updateOrCreate(
            ['name' => 'Demo Insights App'],
            [
                'status' => AppStatus::Active,
                'created_at' => now(),
            ],
        );

        $installation = AppInstallation::query()->updateOrCreate(
            ['store_id' => $store->id, 'app_id' => $app->id],
            [
                'scopes_json' => ['read-products', 'read-orders', 'read-analytics'],
                'status' => AppInstallationStatus::Active,
                'installed_at' => now()->subDay(),
            ],
        );

        OauthClient::query()->updateOrCreate(
            ['client_id' => 'demo-client-id'],
            [
                'app_id' => $app->id,
                'client_secret_encrypted' => encrypt('demo-client-secret'),
                'redirect_uris_json' => ['https://app.example.test/callback'],
            ],
        );

        OauthToken::query()->updateOrCreate(
            ['access_token_hash' => hash('sha256', 'demo-access-token')],
            [
                'installation_id' => $installation->id,
                'refresh_token_hash' => hash('sha256', 'demo-refresh-token'),
                'expires_at' => now()->addMonth(),
            ],
        );

        $subscription = WebhookSubscription::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'event_type' => 'order.created',
                'target_url' => 'https://app.example.test/webhooks/order-created',
            ],
            [
                'app_installation_id' => $installation->id,
                'signing_secret_encrypted' => encrypt('demo-signing-secret'),
                'status' => WebhookSubscriptionStatus::Active,
            ],
        );

        WebhookDelivery::query()->updateOrCreate(
            ['subscription_id' => $subscription->id, 'event_id' => 'order-1001-created'],
            [
                'attempt_count' => 1,
                'status' => WebhookDeliveryStatus::Success,
                'last_attempt_at' => now()->subMinutes(5),
                'response_code' => 200,
                'response_body_snippet' => 'ok',
            ],
        );
    }
}
