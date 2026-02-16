<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WebhookSubscription> */
class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'target_url' => fake()->url(),
            'secret' => Str::random(32),
            'event_types_json' => ['order.created', 'order.updated'],
            'status' => 'active',
            'consecutive_failures' => 0,
        ];
    }
}
