<?php

use App\Enums\StoreUserRole;
use App\Http\Middleware\CheckStoreRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function callCheckStoreRole(Store $store, User $user, array $roles): Response
{
    app()->instance('current_store', $store);

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(fn () => $user);

    $middleware = new CheckStoreRole;

    return $middleware->handle($request, fn () => new Response('ok'), ...$roles);
}

test('owner can access role-restricted route', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => StoreUserRole::Owner]);

    $response = callCheckStoreRole($store, $user, ['owner', 'admin']);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('ok');
});

test('admin can access owner-or-admin restricted route', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => StoreUserRole::Admin]);

    $response = callCheckStoreRole($store, $user, ['owner', 'admin']);

    expect($response->getStatusCode())->toBe(200);
});

test('staff cannot access owner-or-admin restricted route', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => StoreUserRole::Staff]);

    callCheckStoreRole($store, $user, ['owner', 'admin']);
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);

test('user without store access gets 403', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    callCheckStoreRole($store, $user, ['owner', 'admin']);
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
