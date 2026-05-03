<?php

namespace Database\Seeders;

use App\Models\AppInstallation;
use App\Models\OauthToken;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OauthTokenSeeder extends Seeder
{
    public function run(): void
    {
        AppInstallation::withoutGlobalScopes()->get()->each(function (AppInstallation $installation): void {
            OauthToken::query()->updateOrCreate(
                ['installation_id' => $installation->id],
                [
                    'access_token_hash' => hash('sha256', Str::random(64)),
                    'refresh_token_hash' => hash('sha256', Str::random(64)),
                    'expires_at' => now()->addHour(),
                ],
            );
        });
    }
}
