<?php

use App\Enums\AppInstallationStatus;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\OauthClient;
use App\Models\OauthToken;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('app installation oauth models cast scopes dates and encrypted secrets', function () {
    $store = Store::factory()->create();
    $app = App::factory()->create();
    $installation = AppInstallation::factory()->for($store)->for($app)->create([
        'scopes_json' => ['read-products', 'write-products'],
    ]);
    $client = OauthClient::factory()->for($app)->create(['client_secret_encrypted' => 'plain-secret']);
    $token = OauthToken::factory()->for($installation, 'installation')->create();

    expect($installation->status)->toBe(AppInstallationStatus::Active)
        ->and($installation->scopes_json)->toBe(['read-products', 'write-products'])
        ->and($installation->tokens->sole()->is($token))->toBeTrue()
        ->and($client->client_secret_encrypted)->toBe('plain-secret')
        ->and(DB::table('oauth_clients')->whereKey($client->id)->value('client_secret_encrypted'))->not->toBe('plain-secret');

});
