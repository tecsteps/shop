<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    public function definition(): array
    {
        return [
            'subscription_id' => WebhookSubscription::factory(),
            'event_id' => (string) Str::uuid(),
            'attempt_count' => 1,
            'status' => WebhookDeliveryStatus::Pending,
        ];
    }
}
