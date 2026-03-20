<?php

use App\Http\Middleware\ResolveStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'resolve.store' => ResolveStore::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\ResolveStoreFromHostname::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('account', 'account/*')) {
                return route('customer.login');
            }

            if ($request->is('admin', 'admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
