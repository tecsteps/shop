<?php

namespace Database\Factories;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookSubscription>
 */
class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'event_type' => 'order.created',
            'target_url' => 'https://example.com/webhooks/'.Str::random(8),
            'signing_secret_encrypted' => Crypt::encryptString('whsec_'.Str::random(32)),
            'status' => WebhookSubscriptionStatus::Active,
            'consecutive_failures' => 0,
        ];
    }
}
