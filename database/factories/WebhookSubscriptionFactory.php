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
                'order.created', 'order.updated', 'product.created', 'product.updated',
            ]),
            'target_url' => fake()->url(),
            'secret' => Str::random(32),
            'status' => 'active',
            'consecutive_failures' => 0,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'paused',
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'deleted',
        ]);
    }
}
