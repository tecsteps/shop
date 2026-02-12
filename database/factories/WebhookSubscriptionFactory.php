<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookSubscription> */
class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'topic' => 'orders/create',
            'url' => 'https://example.com/webhooks',
            'is_active' => true,
        ];
    }
}
