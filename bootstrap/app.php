<?php

use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\CheckTokenAbility;
use App\Http\Middleware\CustomerAuthenticate;
use App\Http\Middleware\ResolveStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'store.resolve' => ResolveStore::class,
            'role.check' => CheckStoreRole::class,
            'auth.customer' => CustomerAuthenticate::class,
            'ability' => CheckTokenAbility::class,
        ]);

        // Guests hitting the admin panel go to the admin login; everything
        // else is storefront-facing and goes to the customer login.
        $middleware->redirectGuestsTo(
            fn (Illuminate\Http\Request $request): string => $request->is('admin', 'admin/*') ? '/admin/login' : '/account/login',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Cart optimistic-concurrency conflicts return 409 with the current
        // cart state in the response body (spec 02 §2.1, spec 05 §4.3).
        $exceptions->render(function (App\Exceptions\CartVersionMismatchException $exception, Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(
                array_merge(
                    ['message' => $exception->getMessage()],
                    (new App\Http\Resources\Storefront\CartResource($exception->cart->refresh()))->toArray($request),
                ),
                409,
            );
        });

        // Invalid checkout state transitions map to 422 (spec 02 §2.2).
        $exceptions->render(function (App\Exceptions\InvalidCheckoutTransitionException $exception, Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json(['message' => $exception->getMessage()], 422);
        });
    })->create();
