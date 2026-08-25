<?php

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
            'store.resolve' => \App\Http\Middleware\ResolveStore::class,
            'role.check' => \App\Http\Middleware\CheckStoreRole::class,
            'auth.customer' => \App\Http\Middleware\CustomerAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\CartVersionMismatchException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The cart has been modified. Please refresh and try again.',
                    'error_code' => 'version_conflict',
                ], 409);
            }
        });
    })->create();
