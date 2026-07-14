<?php

use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\CustomerAuthenticate;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ResolveStore;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            'user.active' => EnsureUserIsActive::class,
            'abilities' => CheckAbilities::class,
        ]);
        $middleware->web(append: [EnsureUserIsActive::class]);
        $middleware->group('storefront', [ResolveStore::class]);
        $middleware->group('storefront.api', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ResolveStore::class,
        ]);
        $middleware->group('admin.store', [ResolveStore::class]);
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolveStore::class);
        $middleware->redirectGuestsTo(fn ($request): string => $request->is('account', 'account/*')
            ? '/account/login'
            : ($request->is('admin', 'admin/*') ? '/admin/login' : '/login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
