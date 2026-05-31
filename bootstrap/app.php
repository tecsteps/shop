<?php

use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\CustomerAuthenticate;
use App\Http\Middleware\ResolveStore;
use App\Http\Middleware\ResolveStoreFromRoute;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/storefront.php'));

            Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Illuminate\Support\Facades\Route::group([], base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Custom middleware aliases.
        $middleware->alias([
            'store.resolve' => ResolveStore::class,
            'store.resolve.route' => ResolveStoreFromRoute::class,
            'role.check' => CheckStoreRole::class,
            'auth.customer' => CustomerAuthenticate::class,
            'ability' => CheckTokenAbility::class,
        ]);

        // Route middleware groups for tenant resolution.
        $middleware->group('storefront', [
            ResolveStore::class.':storefront',
        ]);

        $middleware->group('admin', [
            ResolveStore::class.':admin',
        ]);

        // Unauthenticated admin (web guard) requests redirect to the admin login.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
