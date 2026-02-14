<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\ResolveStore;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
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
            'auth' => Authenticate::class,
            'store.resolve' => ResolveStore::class,
            'role.check' => CheckStoreRole::class,
        ]);

        // Customer auth provider is store-scoped, so tenant resolution must run before auth middleware.
        $middleware->prependToPriorityList(AuthenticatesRequests::class, ResolveStore::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
