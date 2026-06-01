<?php

use App\Http\Middleware\ResolveStore;
use App\Livewire\Storefront\CartDrawer;
use Illuminate\Http\Request;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Livewire update tenancy (regression for task #18)
|--------------------------------------------------------------------------
|
| ResolveStore runs in the storefront/admin route-group middleware on the
| initial page GET, but the shared /livewire/update endpoint does not re-apply
| those groups. Without the persistent-middleware fix, nested/global components
| (e.g. the layout CartDrawer) that read app('current_store') 500 on update
| requests with "Target class [current_store] does not exist".
|
| These tests exercise ResolveStore the way Livewire invokes persistent
| middleware on /livewire/update: with NO explicit mode argument and with the
| current_store binding cleared, asserting the store is re-bound for both the
| storefront (host-based) and admin (session-based) surfaces.
|
*/

/**
 * Forget any current_store binding to mimic a fresh /livewire/update request,
 * where the route-group middleware has not run.
 */
function forgetCurrentStore(): void
{
    if (app()->bound('current_store')) {
        app()->forgetInstance('current_store');
    }
}

/**
 * Run ResolveStore exactly as Livewire's persistent-middleware replay does:
 * no explicit mode, on a request that carries the originating page as Referer.
 * Pass a null referer to simulate a stripped/absent Referer header. The host of
 * the update endpoint defaults to shop.test but can be overridden.
 */
function runResolveStoreForUpdate(?string $referer, string $host = 'shop.test'): Request
{
    $request = Request::create("http://{$host}/livewire/update", 'POST');

    if ($referer !== null) {
        $request->headers->set('referer', $referer);
    }

    $request->setLaravelSession(app('session')->driver());

    app(ResolveStore::class)->handle($request, fn ($req) => response('ok'));

    return $request;
}

it('registers ResolveStore as Livewire persistent middleware', function () {
    expect(Livewire::getPersistentMiddleware())->toContain(ResolveStore::class);
});

it('rebinds the storefront store on a livewire update request', function () {
    $context = createStoreContext(['hostname' => 'shop.test', 'bind' => false]);
    forgetCurrentStore();

    runResolveStoreForUpdate('http://shop.test/');

    expect(app()->bound('current_store'))->toBeTrue()
        ->and(app('current_store')->id)->toBe($context['store']->id);
});

it('rebinds the admin store on a livewire update request via the session', function () {
    $context = createStoreContext(['hostname' => 'shop.test', 'bind' => false]);
    actingAsAdmin($context['owner'], $context['store']);
    forgetCurrentStore();

    $request = runResolveStoreForUpdate('http://shop.test/admin/products');

    // Admin surface resolves from the session, not the host.
    expect(app()->bound('current_store'))->toBeTrue()
        ->and(app('current_store')->id)->toBe($context['store']->id);
});

it('is idempotent when a store is already bound', function () {
    $context = createStoreContext(['hostname' => 'shop.test', 'bind' => false]);
    $other = createStoreContext(['hostname' => 'other.test', 'bind' => false]);

    bindCurrentStore($context['store']);

    // Even with an admin Referer, an already-bound store must not be replaced.
    runResolveStoreForUpdate('http://shop.test/admin');

    expect(app('current_store')->id)->toBe($context['store']->id);
});

it('does not 500 driving a global CartDrawer update when the store starts unbound', function () {
    $context = createStoreContext(['hostname' => 'shop.test', 'bind' => false]);

    // Simulate the persistent middleware having re-bound the store on the
    // update request (the real /livewire/update flow), then drive a mutation.
    forgetCurrentStore();
    runResolveStoreForUpdate('http://shop.test/');

    Livewire::test(CartDrawer::class)
        ->call('openDrawer')
        ->assertOk();

    expect(app('current_store')->id)->toBe($context['store']->id);
});

it('resolves storefront on a storefront host even with a stale admin session and no referer', function () {
    // The exact edge case storefront flagged: a web/admin session lingers in the
    // same browser while shopping the storefront, and the Referer is stripped.
    // The storefront host must win over the session+web-guard heuristic so the
    // update does not get hijacked into admin mode (which would 404).
    $context = createStoreContext(['hostname' => 'shop.test', 'bind' => false]);
    actingAsAdmin($context['owner'], $context['store']); // web guard + session current_store_id
    forgetCurrentStore();

    runResolveStoreForUpdate(referer: null, host: 'shop.test');

    expect(app()->bound('current_store'))->toBeTrue()
        ->and(app('current_store')->id)->toBe($context['store']->id);
});

it('still falls back to admin via session when the host is not a storefront domain', function () {
    // On a non-storefront host (e.g. an admin-only host) with no Referer, an
    // authenticated web user with a selected store resolves in admin mode.
    $context = createStoreContext(['hostname' => 'shop.test', 'bind' => false]);
    actingAsAdmin($context['owner'], $context['store']);
    forgetCurrentStore();

    runResolveStoreForUpdate(referer: null, host: 'admin-only.test');

    expect(app()->bound('current_store'))->toBeTrue()
        ->and(app('current_store')->id)->toBe($context['store']->id);
});
