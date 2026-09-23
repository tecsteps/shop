<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Layout\TopBar;
use App\Livewire\Admin\SelectStore;
use App\Livewire\Admin\Settings\Domains;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

function domainAdmin(Store $store, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => $role]);

    return $user;
}

it('selects a store when an administrator belongs to more than one store', function () {
    $firstStore = shopStore('selection-first.test');
    $secondStore = shopStore('selection-second.test');
    $user = domainAdmin($firstStore);
    $user->stores()->attach($secondStore->id, ['role' => 'admin']);

    $this->actingAs($user)->get('/admin')
        ->assertRedirect(route('admin.select-store'));

    $this->actingAs($user)->get(route('admin.select-store'))
        ->assertOk()
        ->assertSee('Choose a store')
        ->assertSee($firstStore->name)
        ->assertSee($secondStore->name);

    Livewire::actingAs($user)
        ->test(SelectStore::class)
        ->call('select', (string) $secondStore->id)
        ->assertRedirectToRoute('admin.dashboard');

    $this->assertSame($secondStore->id, (int) session('current_store_id'));
});

it('redirects a successful login to store selection when the account has multiple stores', function () {
    $firstStore = shopStore('login-first-selection.test');
    $secondStore = shopStore('login-second-selection.test');
    $user = User::factory()->create(['email' => 'multiple-stores@shop.test', 'password' => 'password']);
    $user->stores()->attach([$firstStore->id => ['role' => 'owner'], $secondStore->id => ['role' => 'admin']]);

    Livewire::test(AdminLogin::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirectToRoute('admin.select-store');
});

it('renders the domains tab within the admin settings route', function () {
    $store = shopStore('settings-domain-tab.test');
    $user = domainAdmin($store);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get(route('admin.settings', ['tab' => 'domains']))
        ->assertOk()
        ->assertSee('Domains')
        ->assertSee('settings-domain-tab.test')
        ->assertSee('Add domain');
});

it('switches the current store only to a store the administrator can access', function () {
    $firstStore = shopStore('switch-first.test');
    $secondStore = shopStore('switch-second.test');
    $foreignStore = shopStore('switch-foreign.test');
    $user = domainAdmin($firstStore);
    $user->stores()->attach($secondStore->id, ['role' => 'admin']);
    app()->instance('current_store', $firstStore);
    session()->put('current_store_id', $firstStore->id);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee($firstStore->name)
        ->assertSee($secondStore->name)
        ->call('switchStore', (string) $secondStore->id)
        ->assertRedirectToRoute('admin.dashboard');

    $this->assertSame($secondStore->id, (int) session('current_store_id'));

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->call('switchStore', (string) $foreignStore->id)
        ->assertForbidden();

    $this->assertSame($secondStore->id, (int) session('current_store_id'));
});

it('adds normalized domains with a primary per domain type', function () {
    $store = Store::factory()->create();
    $user = domainAdmin($store);
    session()->put('current_store_id', $store->id);

    Livewire::actingAs($user)
        ->test(Domains::class)
        ->call('openAddDomainModal')
        ->set('newHostname', '  Shop.Example.Test  ')
        ->set('newType', 'storefront')
        ->call('addDomain')
        ->assertHasNoErrors()
        ->assertSet('showAddDomainModal', false)
        ->assertSee('shop.example.test')
        ->assertSee('Primary');

    $domain = StoreDomain::query()->where('hostname', 'shop.example.test')->firstOrFail();
    expect($domain->store_id)->toBe($store->id)
        ->and($domain->type)->toBe('storefront')
        ->and($domain->is_primary)->toBeTrue()
        ->and($domain->tls_mode)->toBe('managed');
});

it('rejects invalid duplicate and cross-tenant domain mutations', function () {
    $store = shopStore();
    $otherStore = shopStore('domain-other-store.test');
    $user = domainAdmin($store);
    app()->instance('current_store', $store);
    $foreignDomain = $otherStore->domains()->firstOrFail();

    Livewire::actingAs($user)
        ->test(Domains::class)
        ->set('newHostname', 'https://not-a-hostname.test/path')
        ->call('addDomain')
        ->assertHasErrors('newHostname')
        ->set('newHostname', $foreignDomain->hostname)
        ->call('addDomain')
        ->assertHasErrors('newHostname');

    expect(fn () => Livewire::actingAs($user)->test(Domains::class)->call('removeDomain', $foreignDomain->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $this->assertModelExists($foreignDomain);
    $this->assertDatabaseMissing('store_domains', ['store_id' => $store->id, 'hostname' => 'not-a-hostname.test']);
});

it('changes primary domains only within the selected store and domain type', function () {
    $store = shopStore();
    $user = domainAdmin($store);
    $currentPrimary = $store->domains()->where('type', 'storefront')->firstOrFail();
    $newPrimary = $store->domains()->create(['hostname' => 'secondary-storefront.test', 'type' => 'storefront', 'is_primary' => false, 'tls_mode' => 'managed']);
    $adminPrimary = $store->domains()->create(['hostname' => 'admin-domain.test', 'type' => 'admin', 'is_primary' => true, 'tls_mode' => 'managed']);
    app()->instance('current_store', $store);
    Cache::put('store-domain:secondary-storefront.test', $store->id, now()->addMinute());

    Livewire::actingAs($user)->test(Domains::class)->call('setPrimary', $newPrimary->id)->assertHasNoErrors();

    expect($newPrimary->refresh()->is_primary)->toBeTrue()
        ->and($currentPrimary->refresh()->is_primary)->toBeFalse()
        ->and($adminPrimary->refresh()->is_primary)->toBeTrue()
        ->and(Cache::get('store-domain:secondary-storefront.test'))->toBeNull();
});

it('promotes the next domain when a primary is removed and denies non-admin domain management', function () {
    $store = shopStore();
    $primary = $store->domains()->firstOrFail();
    $backup = $store->domains()->create(['hostname' => 'backup-domain.test', 'type' => 'storefront', 'is_primary' => false, 'tls_mode' => 'managed']);
    $owner = domainAdmin($store);
    app()->instance('current_store', $store);
    Cache::put('store-domain:'.$primary->hostname, $store->id, now()->addMinute());

    Livewire::actingAs($owner)->test(Domains::class)->call('removeDomain', $primary->id)->assertHasNoErrors();

    expect($backup->refresh()->is_primary)->toBeTrue()
        ->and(Cache::get('store-domain:'.$primary->hostname))->toBeNull();

    $staff = domainAdmin($store, 'staff');
    Livewire::actingAs($staff)->test(Domains::class)->assertForbidden();
});
