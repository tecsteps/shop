<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\OauthClient>
 */
class OauthClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'client_id' => 'client_'.Str::random(24),
            'client_secret_encrypted' => Str::random(40),
            'redirect_uris_json' => ['https://example.com/oauth/callback'],
        ];
    }
}
