<?php

namespace Database\Factories;

use App\Models\AppInstallation;
use App\Models\OauthToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OauthToken>
 */
class OauthTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'installation_id' => AppInstallation::factory(),
            'access_token_hash' => hash('sha256', Str::random(40)),
            'refresh_token_hash' => hash('sha256', Str::random(40)),
            'expires_at' => now()->addHour()->toIso8601String(),
        ];
    }
}
