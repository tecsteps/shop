<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\CustomerAuthenticate;
use App\Http\Middleware\ResolveStore;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'store.resolve' => ResolveStore::class,
            'role.check' => CheckStoreRole::class,
            'customer.auth' => CustomerAuthenticate::class,
            'abilities' => CheckAbilities::class,
        ]);
        $middleware->group('storefront', [ResolveStore::class]);
        $middleware->group('admin.store', [ResolveStore::class]);
        $middleware->redirectGuestsTo(fn ($request): string => $request->is('account/*') ? '/account/login' : '/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
