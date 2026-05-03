<?php

namespace Database\Factories;

use App\Models\AppInstallation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OauthToken>
 */
class OauthTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'installation_id' => AppInstallation::factory(),
            'access_token_hash' => hash('sha256', Str::random(64)),
            'refresh_token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addHour(),
        ];
    }
}
