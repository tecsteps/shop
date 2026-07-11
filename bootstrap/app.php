<?php

use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\CustomerAuthenticate;
use App\Http\Middleware\ResolveStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request): string => match (true) {
            $request->is('admin', 'admin/*') => route('admin.login'),
            $request->is('account', 'account/*') => route('storefront.account.login'),
            default => route('login'),
        });
        $middleware->redirectUsersTo(fn (Request $request): string => $request->is('admin', 'admin/*')
            ? route('admin.dashboard')
            : route('dashboard'));

        $middleware->alias([
            'role.check' => CheckStoreRole::class,
            'store.resolve' => ResolveStore::class,
            'customer.auth' => CustomerAuthenticate::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        $middleware->group('storefront', [
            ResolveStore::class,
        ]);

        $middleware->group('admin', [
            ResolveStore::class,
            CheckStoreRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
