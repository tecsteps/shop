<?php

use App\Enums\StoreUserRole;
use App\Http\Middleware\ResolveStore;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('storefront routes resolve store from hostname', function () {
    $store = Store::factory()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'test-store.example.com',
    ]);

    $request = Request::create('http://test-store.example.com/account/login', 'GET');
    $request->setLaravelSession(app('session.store'));

    $middleware = new ResolveStore;
    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
    expect(app('current_store')->id)->toBe($store->id);
});

test('storefront routes return 404 for unknown hostname', function () {
    $request = Request::create('http://unknown.example.com/products', 'GET');
    $request->setLaravelSession(app('session.store'));

    $middleware = new ResolveStore;
    $middleware->handle($request, fn () => new Response('ok'));
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);

test('storefront routes return 503 for suspended store', function () {
    $store = Store::factory()->suspended()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'suspended.example.com',
    ]);

    $request = Request::create('http://suspended.example.com/products', 'GET');
    $request->setLaravelSession(app('session.store'));

    $middleware = new ResolveStore;
    $middleware->handle($request, fn () => new Response('ok'));
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);

test('admin routes resolve store from session', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => StoreUserRole::Owner]);

    $request = Request::create('http://localhost/admin/dashboard', 'GET');
    $session = app('session.store');
    $session->put('current_store_id', $store->id);
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $middleware = new ResolveStore;
    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
    expect(app('current_store')->id)->toBe($store->id);
});

test('admin routes return 403 when user has no store access', function () {
    $store = Store::factory()->create();
    $user = User::factory()->create();

    $request = Request::create('http://localhost/admin/dashboard', 'GET');
    $session = app('session.store');
    $session->put('current_store_id', $store->id);
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $middleware = new ResolveStore;
    $middleware->handle($request, fn () => new Response('ok'));
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
