<?php

namespace Database\Factories;

use App\Enums\WebhookSubscriptionStatus;
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
            'event_type' => fake()->randomElement([
                'order.created', 'order.paid', 'order.fulfilled',
                'product.created', 'product.updated',
            ]),
            'target_url' => fake()->url(),
            'signing_secret_encrypted' => Str::random(32),
            'status' => WebhookSubscriptionStatus::Active,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookSubscriptionStatus::Paused,
        ]);
    }
}
