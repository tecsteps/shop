<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookSubscription>
 */
class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'event_type' => fake()->randomElement(['order.created', 'order.paid', 'order.fulfilled']),
            'url' => fake()->url(),
            'secret' => Str::random(40),
            'status' => 'active',
            'failed_count' => 0,
        ];
    }
}
