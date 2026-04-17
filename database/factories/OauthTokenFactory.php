<?php

namespace Database\Factories;

use App\Models\AppInstallation;
use App\Models\OauthToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OauthToken>
 */
class OauthTokenFactory extends Factory
{
    protected $model = OauthToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'installation_id' => AppInstallation::factory(),
            'access_token_hash' => hash('sha256', fake()->unique()->uuid()),
            'refresh_token_hash' => hash('sha256', fake()->uuid()),
            'expires_at' => now()->addHour()->toIso8601String(),
        ];
    }
}
