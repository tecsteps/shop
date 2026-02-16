<?php

namespace Database\Factories;

use App\Models\AppModel;
use App\Models\OauthClient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<OauthClient> */
class OauthClientFactory extends Factory
{
    protected $model = OauthClient::class;

    public function definition(): array
    {
        return [
            'app_id' => AppModel::factory(),
            'client_id' => Str::uuid()->toString(),
            'client_secret' => Str::random(40),
            'redirect_uri' => fake()->url(),
        ];
    }
}
