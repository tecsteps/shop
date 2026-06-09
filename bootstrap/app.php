<?php

use App\Http\Middleware\ResolveStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'store.resolve' => ResolveStore::class,
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
