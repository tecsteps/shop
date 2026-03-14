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
            'event_type' => fake()->randomElement([
                'order.created',
                'order.paid',
                'order.fulfilled',
                'order.cancelled',
                'product.created',
                'product.updated',
                'customer.created',
            ]),
            'target_url' => fake()->url().'/webhooks',
            'secret' => Str::random(32),
            'status' => 'active',
            'consecutive_failures' => 0,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disabled',
        ]);
    }
}
