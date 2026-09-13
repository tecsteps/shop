<?php

namespace Database\Factories;

use App\Models\AppInstallation;
use Illuminate\Database\Eloquent\Factories\Factory;

class OauthTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'installation_id' => AppInstallation::factory(),
            'access_token_hash' => hash('sha256', fake()->unique()->uuid()),
            'refresh_token_hash' => hash('sha256', fake()->uuid()),
            'expires_at' => fake()->dateTimeBetween('+1 hour', '+1 month'),
        ];
    }
}
