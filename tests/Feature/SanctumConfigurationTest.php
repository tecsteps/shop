<?php

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

test('api routes accept stateful Sanctum requests', function (): void {
    $route = collect(app('router')->getRoutes()->getRoutes())
        ->first(fn ($route): bool => $route->uri() === 'api/admin/v1/stores/{storeId}/me');

    expect(app('router')->gatherRouteMiddleware($route))
        ->toContain(EnsureFrontendRequestsAreStateful::class);
});
