<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WebhookSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

        WebhookSubscription::withoutGlobalScopes()->updateOrCreate(
            [
                'store_id' => $store->id,
                'event_type' => 'order.created',
                'target_url' => 'https://example.com/webhooks/orders',
            ],
            [
                'signing_secret_encrypted' => Str::random(40),
                'status' => 'disabled',
            ],
        );
    }
}
