<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\OauthClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OauthClient>
 */
class OauthClientFactory extends Factory
{
    protected $model = OauthClient::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'client_id' => Str::uuid()->toString(),
            'client_secret_encrypted' => Str::random(40),
            'redirect_uris_json' => ['https://example.com/callback'],
        ];
    }
}
