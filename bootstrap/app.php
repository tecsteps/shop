<?php

use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\CustomerAuthenticate;
use App\Http\Middleware\ResolveStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin', 'admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->appendToGroup('storefront', [
            ResolveStore::class.':storefront',
        ]);

        $middleware->appendToGroup('admin', [
            ResolveStore::class.':admin',
        ]);

        $middleware->alias([
            'store.resolve' => ResolveStore::class,
            'role.check' => CheckStoreRole::class,
            'auth.customer' => CustomerAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
