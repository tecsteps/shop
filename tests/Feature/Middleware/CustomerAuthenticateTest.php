<?php

use App\Http\Middleware\CustomerAuthenticate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated customer is redirected to login', function () {
    Auth::shouldReceive('guard')->with('customer')->andReturn(
        Mockery::mock()->shouldReceive('check')->andReturn(false)->getMock()
    );

    $request = Request::create('http://shop.example.com/account', 'GET');
    $session = app('session.store');
    $request->setLaravelSession($session);

    $middleware = new CustomerAuthenticate;
    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))->toContain('/account/login');
});

test('authenticated customer can proceed', function () {
    Auth::shouldReceive('guard')->with('customer')->andReturn(
        Mockery::mock()->shouldReceive('check')->andReturn(true)->getMock()
    );

    $request = Request::create('http://shop.example.com/account', 'GET');
    $session = app('session.store');
    $request->setLaravelSession($session);

    $middleware = new CustomerAuthenticate;
    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('ok');
});
