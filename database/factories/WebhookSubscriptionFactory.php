<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'event_type' => 'order.created',
            'target_url' => 'https://example.com/webhook',
            'signing_secret_encrypted' => 'test-secret',
            'status' => 'active',
        ];
    }
}
