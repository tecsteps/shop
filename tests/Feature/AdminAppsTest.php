<?php

use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Apps\Show as AppShow;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function appsAdmin(Store $store, string $role = 'owner'): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => $role]);

    return $user;
}

function createAppInstallation(Store $store, string $name, string $handle, string $status = 'active'): int
{
    $now = now();
    $appId = DB::table('apps')->insertGetId([
        'name' => $name,
        'handle' => $handle,
        'description' => 'A test app.',
        'scopes_json' => json_encode(['read-products']),
        'is_available' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('app_installations')->insert([
        'store_id' => $store->id,
        'app_id' => $appId,
        'status' => $status,
        'settings_json' => '{}',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return $appId;
}

it('shows the apps empty state when the current store has no installations', function () {
    $store = shopStore('apps-empty.test');
    $otherStore = shopStore('apps-other.test');
    $user = appsAdmin($store);
    createAppInstallation($otherStore, 'Other store app', 'other-store-app');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->id])
        ->get(route('admin.apps'))
        ->assertOk()
        ->assertSee('Apps')
        ->assertSee('No apps installed')
        ->assertDontSee('Other store app');
});

it('lists and uninstalls only apps installed for the current store', function () {
    $store = shopStore('apps-installed.test');
    $otherStore = shopStore('apps-installed-other.test');
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    app()->instance('current_store', $store);
    $localAppId = createAppInstallation($store, 'Store reporting', 'store-reporting');
    $foreignAppId = createAppInstallation($otherStore, 'Private analytics', 'private-analytics');

    Livewire::actingAs($user)->test(AppsIndex::class)
        ->assertSee('Store reporting')
        ->assertDontSee('Private analytics')
        ->call('uninstallApp', $foreignAppId)
        ->assertStatus(404);

    expect(DB::table('app_installations')->where('store_id', $otherStore->id)->where('app_id', $foreignAppId)->exists())->toBeTrue();

    $localComponent = Livewire::actingAs($user)->test(AppsIndex::class);
    app()->forgetInstance('current_store');

    $localComponent->call('uninstallApp', $localAppId)
        ->assertHasNoErrors();

    expect(DB::table('app_installations')->where('store_id', $store->id)->where('app_id', $localAppId)->exists())->toBeFalse()
        ->and(DB::table('app_installations')->where('store_id', $otherStore->id)->where('app_id', $foreignAppId)->exists())->toBeTrue();
});

it('restricts apps management to store owners and admins', function () {
    $store = shopStore('apps-security.test');
    $staff = appsAdmin($store, 'staff');

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $store->id])
        ->get(route('admin.apps'))
        ->assertForbidden();
});

it('shows installation scopes and only its store-scoped webhook subscriptions', function () {
    $store = shopStore('apps-detail.test');
    $otherStore = shopStore('apps-detail-other.test');
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    app()->instance('current_store', $store);
    $appId = createAppInstallation($store, 'Order alerts', 'order-alerts');
    $otherAppId = createAppInstallation($otherStore, 'Private alerts', 'private-alerts');
    $installationId = (int) DB::table('app_installations')->where('store_id', $store->id)->where('app_id', $appId)->value('id');
    $otherInstallationId = (int) DB::table('app_installations')->where('store_id', $otherStore->id)->where('app_id', $otherAppId)->value('id');
    $now = now();
    DB::table('webhook_subscriptions')->insert([
        [
            'store_id' => $store->id,
            'app_installation_id' => $installationId,
            'event_type' => 'orders.created',
            'target_url' => 'https://hooks.example.test/orders',
            'signing_secret_encrypted' => 'encrypted-test-secret',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'store_id' => $otherStore->id,
            'app_installation_id' => $otherInstallationId,
            'event_type' => 'orders.refunded',
            'target_url' => 'https://private.example.test/orders',
            'signing_secret_encrypted' => 'encrypted-private-secret',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    Livewire::actingAs($user)
        ->test(AppShow::class, ['installation' => $installationId])
        ->assertSee('read-products')
        ->assertSee('orders.created')
        ->assertSee('https://hooks.example.test/orders')
        ->assertSee('No API calls recorded')
        ->assertDontSee('orders.refunded')
        ->assertDontSee('private.example.test');

    Livewire::actingAs($user)
        ->test(AppShow::class, ['installation' => $otherInstallationId])
        ->assertStatus(404);
});
