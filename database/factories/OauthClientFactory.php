<?php

namespace Database\Factories;

use App\Models\App;
use App\Models\OauthClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @extends Factory<OauthClient>
 */
class OauthClientFactory extends Factory
{
    protected $model = OauthClient::class;

    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'client_id' => 'client_'.Str::random(24),
            'client_secret_encrypted' => Crypt::encryptString('secret_'.Str::random(32)),
            'redirect_uris_json' => ['https://example.com/callback'],
        ];
    }
}
