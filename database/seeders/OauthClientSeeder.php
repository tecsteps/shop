<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\OauthClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OauthClientSeeder extends Seeder
{
    public function run(): void
    {
        App::query()->orderBy('id')->get()->each(function (App $app): void {
            OauthClient::query()->updateOrCreate(
                ['app_id' => $app->id],
                [
                    'client_id' => 'client_'.$app->handle,
                    'client_secret_encrypted' => Str::random(48),
                    'redirect_uris_json' => ['https://example.com/oauth/callback'],
                ],
            );
        });
    }
}
