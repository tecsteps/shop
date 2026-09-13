<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OauthClientFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'app_id' => App::factory(),
            'client_id' => fake()->unique()->uuid(),
            'client_secret_encrypted' => Str::random(64),
            'redirect_uris_json' => [fake()->url()],
        ];
    }
}
