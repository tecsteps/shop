<?php

use App\Http\Middleware\AuthenticateAdminApi;
use App\Http\Middleware\AuthenticatePlatformApi;
use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\ResolveStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.api' => AuthenticateAdminApi::class,
            'platform.api' => AuthenticatePlatformApi::class,
            'role.check' => CheckStoreRole::class,
            'store.resolve' => ResolveStore::class,
        ]);

        $middleware->appendToGroup('storefront', [
            ResolveStore::class,
        ]);

        $middleware->appendToGroup('admin', [
            ResolveStore::class,
            CheckStoreRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
