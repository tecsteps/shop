<?php

namespace Database\Factories;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WebhookSubscription> */
class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'event_type' => 'order.created',
            'target_url' => 'https://example.com/webhooks',
            'signing_secret_encrypted' => 'test-secret',
            'status' => WebhookSubscriptionStatus::Active,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookSubscriptionStatus::Paused,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookSubscriptionStatus::Disabled,
        ]);
    }
}
