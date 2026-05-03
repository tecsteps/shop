<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('admin') || $request->is('admin/*')
            ? route('admin.login')
            : route('login'));

        $middleware->alias([
            'api.token' => App\Http\Middleware\AuthenticateApiToken::class,
            'customer.auth' => App\Http\Middleware\CustomerAuthenticate::class,
            'store.resolve' => App\Http\Middleware\ResolveStore::class,
        ]);

        $middleware->appendToGroup('storefront', [
            App\Http\Middleware\ResolveStore::class,
        ]);

        $middleware->appendToGroup('admin', [
            App\Http\Middleware\ResolveStore::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
