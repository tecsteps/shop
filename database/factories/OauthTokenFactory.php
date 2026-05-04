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
        $token = 'shop_'.Str::random(48);

        return [
            'installation_id' => AppInstallation::factory(),
            'name' => fake()->words(2, true),
            'access_token_hash' => hash('sha256', $token),
            'refresh_token_hash' => hash('sha256', 'refresh_'.Str::random(48)),
            'abilities_json' => ['read-products', 'read-orders'],
            'expires_at' => now()->addYear(),
            'last_used_at' => null,
            'created_at' => now(),
        ];
    }
}
