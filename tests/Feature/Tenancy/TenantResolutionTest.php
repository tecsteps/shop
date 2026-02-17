<?php

use App\Enums\StoreStatus;
use App\Http\Middleware\ResolveStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Create a simulated storefront request with the given hostname.
 */
function storefrontRequest(string $hostname): Request
{
    $request = Request::create("http://{$hostname}/some-page", 'GET');
    $request->setLaravelSession(app('session.store'));

    return $request;
}

/**
 * Create a simulated admin request with session and user resolver.
 */
function adminRequest(int $storeId, $user): Request
{
    $request = Request::create('/admin/dashboard', 'GET');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('current_store_id', $storeId);
    $request->setUserResolver(fn () => $user);

    return $request;
}

it('resolves store from hostname for storefront requests', function (): void {
    $ctx = createStoreContext('acme-fashion.test');
    $middleware = new ResolveStore;

    $request = storefrontRequest('acme-fashion.test');

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getStatusCode())->toBe(200);
    expect(app('current_store')->id)->toBe($ctx['store']->id);
});

it('returns 404 for unknown hostname', function (): void {
    $middleware = new ResolveStore;

    $request = storefrontRequest('nonexistent.test');

    $middleware->handle($request, fn () => new Response('OK'));
})->throws(HttpException::class);

it('returns 503 for suspended store on storefront', function (): void {
    $ctx = createStoreContext('suspended-store.test');
    $ctx['store']->update(['status' => StoreStatus::Suspended]);

    $middleware = new ResolveStore;

    $request = storefrontRequest('suspended-store.test');

    try {
        $middleware->handle($request, fn () => new Response('OK'));
        test()->fail('Expected HttpException was not thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(503);
    }
});

it('resolves store from session for admin requests', function (): void {
    $ctx = createStoreContext();
    $middleware = new ResolveStore;

    $request = adminRequest($ctx['store']->id, $ctx['user']);

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getStatusCode())->toBe(200);
    expect(app('current_store')->id)->toBe($ctx['store']->id);
});

it('denies admin access when user has no store_users record', function (): void {
    $ctx = createStoreContext();
    $outsideUser = User::factory()->create();

    $middleware = new ResolveStore;

    $request = adminRequest($ctx['store']->id, $outsideUser);

    try {
        $middleware->handle($request, fn () => new Response('OK'));
        test()->fail('Expected HttpException was not thrown.');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});

it('caches hostname lookup', function (): void {
    $ctx = createStoreContext('cached-store.test');
    $middleware = new ResolveStore;

    Cache::forget('store-domain:cached-store.test');

    $request = storefrontRequest('cached-store.test');
    $middleware->handle($request, fn () => new Response('OK'));

    expect(Cache::has('store-domain:cached-store.test'))->toBeTrue();
    expect(Cache::get('store-domain:cached-store.test'))->toBe($ctx['store']->id);
});
