<?php

use App\Exceptions\FulfillmentGuardException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Middleware\ResolveStore;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        $middleware->group('storefront', [
            ResolveStore::class.':storefront',
        ]);

        $middleware->group('admin', [
            ResolveStore::class.':admin',
        ]);

        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('storefront.account.login'));

        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Consistent JSON error envelopes for the REST APIs
         * (spec 02 section 10).
         */
        $exceptions->render(function (NotFoundHttpException|ModelNotFoundException $e, Request $request) {
            return $request->is('api/*')
                ? response()->json(['message' => __('The requested resource was not found.')], 404)
                : null;
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) {
            return $request->is('api/*')
                ? response()->json(['message' => __('You do not have permission to perform this action.')], 403)
                : null;
        });

        $exceptions->render(function (InsufficientInventoryException $e, Request $request) {
            return $request->is('api/*')
                ? response()->json([
                    'message' => __('The given data was invalid.'),
                    'errors' => ['quantity' => [$e->getMessage()]],
                ], 422)
                : null;
        });

        $exceptions->render(function (FulfillmentGuardException $e, Request $request) {
            return $request->is('api/*')
                ? response()->json(['message' => $e->getMessage()], 409)
                : null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $isStorefrontRequest = app()->bound('current_store')
                && ! $request->is('admin', 'admin/*')
                && ! $request->is('api/*')
                && ! $request->expectsJson();

            return $isStorefrontRequest
                ? response()->view('storefront.errors.404', [], 404)
                : null;
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            $isMaintenanceResponse = $e->getStatusCode() === 503
                && ! $request->is('admin', 'admin/*')
                && ! $request->is('api/*')
                && ! $request->expectsJson();

            return $isMaintenanceResponse
                ? response()->view('storefront.errors.503', [], 503)
                : null;
        });
    })->create();
