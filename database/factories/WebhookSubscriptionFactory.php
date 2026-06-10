<?php

namespace Database\Factories;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Store;
use App\Services\WebhookService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookSubscription>
 */
class WebhookSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'app_installation_id' => null,
            'event_type' => $this->faker->randomElement(WebhookService::EVENT_TYPES),
            'target_url' => 'https://'.$this->faker->domainName().'/webhooks',
            'signing_secret_encrypted' => 'whsec_'.Str::random(32),
            'status' => WebhookSubscriptionStatus::Active,
            'consecutive_failures' => 0,
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
