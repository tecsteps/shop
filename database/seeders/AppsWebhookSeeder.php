<?php

namespace Database\Seeders;

use App\Models\App as IntegrationApp;
use App\Models\AppInstallation;
use App\Models\OauthClient;
use App\Models\OauthToken;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AppsWebhookSeeder extends Seeder
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

        $loyaltyApp = IntegrationApp::query()->updateOrCreate(
            ['name' => 'Loyalty Rewards'],
            [
                'status' => 'active',
                'created_at' => CarbonImmutable::parse('2026-02-01 08:00:00', 'UTC'),
            ],
        );

        $erpApp = IntegrationApp::query()->updateOrCreate(
            ['name' => 'ERP Connector'],
            [
                'status' => 'active',
                'created_at' => CarbonImmutable::parse('2026-02-01 08:30:00', 'UTC'),
            ],
        );

        $fashionInstallation = null;
        $electronicsInstallation = null;

        if ($stores->has('acme-fashion')) {
            $fashionInstallation = AppInstallation::query()->updateOrCreate(
                [
                    'store_id' => $stores['acme-fashion']->id,
                    'app_id' => $loyaltyApp->id,
                ],
                [
                    'scopes_json' => ['read_orders', 'read_customers'],
                    'status' => 'active',
                    'installed_at' => CarbonImmutable::parse('2026-02-02 10:00:00', 'UTC'),
                ],
            );
        }

        if ($stores->has('acme-electronics')) {
            $electronicsInstallation = AppInstallation::query()->updateOrCreate(
                [
                    'store_id' => $stores['acme-electronics']->id,
                    'app_id' => $erpApp->id,
                ],
                [
                    'scopes_json' => ['read_products', 'read_orders', 'write_inventory'],
                    'status' => 'active',
                    'installed_at' => CarbonImmutable::parse('2026-02-02 10:30:00', 'UTC'),
                ],
            );
        }

        OauthClient::query()->updateOrCreate(
            ['client_id' => 'loyalty-rewards-client'],
            [
                'app_id' => $loyaltyApp->id,
                'client_secret_encrypted' => hash('sha256', 'loyalty-rewards-secret'),
                'redirect_uris_json' => ['https://loyalty.example.test/oauth/callback'],
            ],
        );

        OauthClient::query()->updateOrCreate(
            ['client_id' => 'erp-connector-client'],
            [
                'app_id' => $erpApp->id,
                'client_secret_encrypted' => hash('sha256', 'erp-connector-secret'),
                'redirect_uris_json' => ['https://erp.example.test/oauth/callback'],
            ],
        );

        if ($fashionInstallation) {
            OauthToken::query()->updateOrCreate(
                ['access_token_hash' => hash('sha256', 'fashion-access-token')],
                [
                    'installation_id' => $fashionInstallation->id,
                    'refresh_token_hash' => hash('sha256', 'fashion-refresh-token'),
                    'expires_at' => CarbonImmutable::parse('2026-12-31 23:59:59', 'UTC'),
                ],
            );
        }

        if ($electronicsInstallation) {
            OauthToken::query()->updateOrCreate(
                ['access_token_hash' => hash('sha256', 'electronics-access-token')],
                [
                    'installation_id' => $electronicsInstallation->id,
                    'refresh_token_hash' => hash('sha256', 'electronics-refresh-token'),
                    'expires_at' => CarbonImmutable::parse('2026-12-31 23:59:59', 'UTC'),
                ],
            );
        }

        $fashionOrderCreated = null;
        $electronicsProductUpdated = null;

        if ($stores->has('acme-fashion')) {
            $fashionOrderCreated = WebhookSubscription::query()->updateOrCreate(
                [
                    'store_id' => $stores['acme-fashion']->id,
                    'event_type' => 'order.created',
                    'target_url' => 'https://hooks.acme.test/fashion/orders',
                ],
                [
                    'app_installation_id' => $fashionInstallation?->id,
                    'signing_secret_encrypted' => 'whsec_seed_fashion_orders',
                    'status' => 'active',
                ],
            );
        }

        if ($stores->has('acme-electronics')) {
            $electronicsProductUpdated = WebhookSubscription::query()->updateOrCreate(
                [
                    'store_id' => $stores['acme-electronics']->id,
                    'event_type' => 'product.updated',
                    'target_url' => 'https://hooks.acme.test/electronics/products',
                ],
                [
                    'app_installation_id' => $electronicsInstallation?->id,
                    'signing_secret_encrypted' => 'whsec_seed_electronics_products',
                    'status' => 'active',
                ],
            );
        }

        WebhookDelivery::query()
            ->whereIn('event_id', ['evt-seed-fashion-order-created', 'evt-seed-electronics-product-updated'])
            ->delete();

        $deliveries = [];

        if ($fashionOrderCreated) {
            $deliveries[] = [
                'subscription_id' => $fashionOrderCreated->id,
                'event_id' => 'evt-seed-fashion-order-created',
                'attempt_count' => 1,
                'status' => 'success',
                'last_attempt_at' => CarbonImmutable::parse('2026-02-10 14:00:00', 'UTC'),
                'response_code' => 200,
                'response_body_snippet' => 'ok',
            ];
        }

        if ($electronicsProductUpdated) {
            $deliveries[] = [
                'subscription_id' => $electronicsProductUpdated->id,
                'event_id' => 'evt-seed-electronics-product-updated',
                'attempt_count' => 2,
                'status' => 'failed',
                'last_attempt_at' => CarbonImmutable::parse('2026-02-10 14:05:00', 'UTC'),
                'response_code' => 500,
                'response_body_snippet' => 'upstream timeout',
            ];
        }

        if ($deliveries !== []) {
            WebhookDelivery::query()->insert($deliveries);
        }
    }
}
