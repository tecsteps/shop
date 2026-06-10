<?php

namespace Database\Seeders;

use App\Enums\AppInstallationStatus;
use App\Enums\AppStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\App as AppModel;
use App\Models\AppInstallation;
use App\Models\OauthClient;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AppSeeder extends Seeder
{
    /**
     * Seed the platform app registry, install one app on the fashion demo
     * store, and register webhook subscriptions so the Apps and Developers
     * admin pages have demonstrable data. Idempotent.
     */
    public function run(): void
    {
        $apps = [
            'Loyalty Rewards',
            'Email Marketing Sync',
            'Shipping Labels Pro',
        ];

        foreach ($apps as $name) {
            $app = AppModel::query()->firstOrCreate(
                ['name' => $name],
                ['status' => AppStatus::Active, 'created_at' => now()->subMonths(3)],
            );

            OauthClient::query()->firstOrCreate(
                ['app_id' => $app->getKey()],
                [
                    'client_id' => (string) Str::uuid(),
                    'client_secret_encrypted' => Str::random(40),
                    'redirect_uris_json' => ['https://'.Str::slug($name).'.example.test/oauth/callback'],
                ],
            );
        }

        $fashionStore = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $loyaltyApp = AppModel::query()->where('name', 'Loyalty Rewards')->firstOrFail();

        $installation = AppInstallation::query()->firstOrCreate(
            [
                'store_id' => $fashionStore->getKey(),
                'app_id' => $loyaltyApp->getKey(),
            ],
            [
                'scopes_json' => ['read-products', 'read-orders', 'read-customers'],
                'status' => AppInstallationStatus::Active,
                'installed_at' => now()->subMonths(2),
            ],
        );

        $appSubscription = WebhookSubscription::query()->firstOrCreate(
            [
                'store_id' => $fashionStore->getKey(),
                'app_installation_id' => $installation->getKey(),
                'event_type' => 'order.created',
            ],
            [
                'target_url' => 'https://loyalty-rewards.example.test/webhooks/orders',
                'signing_secret_encrypted' => 'whsec_'.Str::random(32),
                'status' => WebhookSubscriptionStatus::Active,
            ],
        );

        $storeSubscription = WebhookSubscription::query()->firstOrCreate(
            [
                'store_id' => $fashionStore->getKey(),
                'app_installation_id' => null,
                'event_type' => 'order.paid',
            ],
            [
                'target_url' => 'https://erp.acme-fashion.example.test/hooks/payments',
                'signing_secret_encrypted' => 'whsec_'.Str::random(32),
                'status' => WebhookSubscriptionStatus::Active,
            ],
        );

        if ($appSubscription->deliveries()->doesntExist()) {
            WebhookDelivery::factory()->count(2)->succeeded()->create([
                'subscription_id' => $appSubscription->getKey(),
            ]);
        }

        if ($storeSubscription->deliveries()->doesntExist()) {
            WebhookDelivery::factory()->succeeded()->create([
                'subscription_id' => $storeSubscription->getKey(),
            ]);

            WebhookDelivery::factory()->failed()->create([
                'subscription_id' => $storeSubscription->getKey(),
                'attempt_count' => 2,
                'status' => WebhookDeliveryStatus::Pending,
            ]);
        }
    }
}
