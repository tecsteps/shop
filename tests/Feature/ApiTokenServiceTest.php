<?php

use App\Models\PersonalAccessToken;
use App\Models\Store;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('issues a prefixed token and stores its hash with a one-year expiry', function () {
    $this->travelTo(Carbon::parse('2026-01-01 12:00:00'));
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $documentedAbilities = [
        'read-products',
        'write-products',
        'read-orders',
        'write-orders',
        'read-customers',
        'write-customers',
        'read-collections',
        'write-collections',
        'read-discounts',
        'write-discounts',
        'read-analytics',
        'read-settings',
        'write-settings',
        'read-themes',
        'write-themes',
        'read-content',
        'write-content',
        'manage-platform',
    ];

    $result = app(ApiTokenService::class)->create($user, $store, 'Integration token', $documentedAbilities);

    expect(substr($result['plain_text_token'], 0, 5))->toBe('shop_')
        ->and($result['token']->token)->toBe(hash('sha256', $result['plain_text_token']))
        ->and($result['token']->abilities)->toBe($documentedAbilities)
        ->and($result['token']->store_id)->toBe($store->id)
        ->and($result['token']->expires_at->toDateTimeString())->toBe('2027-01-01 12:00:00');

    $this->assertDatabaseHas('personal_access_tokens', [
        'id' => $result['token']->id,
        'token' => hash('sha256', $result['plain_text_token']),
        'store_id' => $store->id,
    ]);
});

it('keeps an explicitly requested token expiry', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $expiresAt = new DateTimeImmutable('2027-02-03 04:05:06+00:00');

    $result = app(ApiTokenService::class)->create($user, $store, 'Temporary token', ['read-orders'], $expiresAt);

    expect($result['token']->expires_at->toDateTimeString())->toBe('2027-02-03 04:05:06');
});

it('rejects undocumented token abilities without creating a token', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    expect(fn () => app(ApiTokenService::class)->create($user, $store, 'Invalid token', ['read-products', 'manage-search']))
        ->toThrow(\InvalidArgumentException::class);

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

it('revokes a token scoped to the requested store', function () {
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $result = app(ApiTokenService::class)->create($user, $store, 'Revocable token', ['read-products']);

    app(ApiTokenService::class)->revoke($result['token'], $store);

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $result['token']->id]);
});

it('does not let another store revoke the token', function () {
    $store = shopStore();
    $otherStore = shopStore('another-token-store.test');
    $owner = User::factory()->create();
    $owner->stores()->attach($store->id, ['role' => 'owner']);
    $result = app(ApiTokenService::class)->create($owner, $store, 'Owned token', ['read-products']);

    expect(fn () => app(ApiTokenService::class)->revoke($result['token'], $otherStore))
        ->toThrow(HttpException::class);

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $result['token']->id]);
});

it('requires the token owner to belong to the requested store', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    expect(fn () => app(ApiTokenService::class)->create($user, $store, 'Unscoped token', ['read-products']))
        ->toThrow(HttpException::class);

    expect(PersonalAccessToken::query()->count())->toBe(0);
});
