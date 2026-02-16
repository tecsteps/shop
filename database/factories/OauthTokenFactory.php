<?php

namespace Database\Factories;

use App\Models\AppInstallation;
use App\Models\OauthToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<OauthToken> */
class OauthTokenFactory extends Factory
{
    protected $model = OauthToken::class;

    public function definition(): array
    {
        return [
            'installation_id' => AppInstallation::factory(),
            'access_token' => Str::random(64),
            'refresh_token' => Str::random(64),
            'scopes_json' => ['read', 'write'],
            'expires_at' => now()->addHour(),
        ];
    }
}
