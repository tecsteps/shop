<?php

namespace Database\Seeders;

use App\Enums\AppInstallationStatus;
use App\Enums\AppStatus;
use App\Enums\WebhookEventType;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\App as AppModel;
use App\Models\AppInstallation;
use App\Models\OauthClient;
use App\Models\OauthToken;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::query()->get()->each(function (Store $store): void {
            $this->seedInstalledApps($store);
        });
    }

    private function seedInstalledApps(Store $store): void
    {
        $apps = [
            [
                'name' => 'Inventory Sync',
                'scopes' => ['read-products', 'write-products', 'read-orders'],
                'events' => [WebhookEventType::OrderCreated, WebhookEventType::ProductUpdated],
            ],
            [
                'name' => 'Fulfillment Bridge',
                'scopes' => ['read-orders', 'write-orders'],
                'events' => [WebhookEventType::OrderCreated, WebhookEventType::FulfillmentCreated],
            ],
        ];

        foreach ($apps as $index => $appData) {
            $app = AppModel::query()->updateOrCreate(
                ['name' => $appData['name']],
                [
                    'status' => AppStatus::Active,
                    'created_at' => now()->subMonths(3 - $index),
                ],
            );

            $installation = AppInstallation::withoutGlobalScopes()->updateOrCreate(
                [
                    'store_id' => $store->getKey(),
                    'app_id' => $app->getKey(),
                ],
                [
                    'scopes_json' => $appData['scopes'],
                    'status' => AppInstallationStatus::Active,
                    'installed_at' => now()->subWeeks(8 - ($index * 3)),
                ],
            );

            OauthClient::query()->updateOrCreate(
                ['client_id' => 'app_client_'.$store->handle.'_'.Str::slug($app->name)],
                [
                    'app_id' => $app->getKey(),
                    'client_secret_encrypted' => 'secret_'.Str::random(40),
                    'redirect_uris_json' => ["https://{$store->handle}.integrations.example/oauth/callback"],
                ],
            );

            OauthToken::query()->updateOrCreate(
                [
                    'installation_id' => $installation->getKey(),
                    'name' => $app->name.' token',
                ],
                [
                    'access_token_hash' => hash('sha256', 'shop_seed_'.$store->handle.'_'.Str::slug($app->name)),
                    'refresh_token_hash' => hash('sha256', 'refresh_seed_'.$store->handle.'_'.Str::slug($app->name)),
                    'abilities_json' => $appData['scopes'],
                    'expires_at' => now()->addYear(),
                    'last_used_at' => $index === 0 ? now()->subHours(2) : null,
                    'created_at' => $installation->installed_at,
                ],
            );

            foreach ($appData['events'] as $eventType) {
                WebhookSubscription::withoutGlobalScopes()->updateOrCreate(
                    [
                        'store_id' => $store->getKey(),
                        'app_installation_id' => $installation->getKey(),
                        'event_type' => $eventType->value,
                    ],
                    [
                        'target_url' => "https://{$store->handle}.integrations.example/webhooks/{$eventType->value}",
                        'signing_secret_encrypted' => 'whsec_'.Str::random(40),
                        'status' => WebhookSubscriptionStatus::Active,
                    ],
                );
            }
        }
    }
}
