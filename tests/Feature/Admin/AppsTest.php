<?php

use App\Livewire\Admin\Apps\Index;
use App\Livewire\Admin\Apps\Show;
use App\Models\App;
use App\Models\AppInstallation;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('renders the apps page with the empty state', function () {
    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/apps')
        ->assertOk()
        ->assertSee('Apps')
        ->assertSee('No apps installed')
        ->assertSee('Available apps')
        ->assertSee('My Integration App');
});

test('installs a catalog app with default scopes', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('installApp', 'Analytics Plugin')
        ->assertDispatched('toast')
        ->assertSee('Analytics Plugin')
        ->assertSee('Active');

    $installation = AppInstallation::query()->sole();

    expect($installation->status)->toBe('active')
        ->and($installation->scopes_json)->toBe(['read-orders', 'read-analytics'])
        ->and($installation->installed_at)->not->toBeNull()
        ->and($installation->app->name)->toBe('Analytics Plugin');
});

test('installing the same app twice reuses the installation', function () {
    Livewire::actingAs($this->user);
    $component = Livewire::test(Index::class);

    $component->call('installApp', 'Review Connector');
    $component->call('uninstallApp', AppInstallation::query()->sole()->id);
    $component->call('installApp', 'Review Connector');

    expect(AppInstallation::query()->count())->toBe(1)
        ->and(AppInstallation::query()->sole()->status)->toBe('active');
});

test('uninstalls an app', function () {
    $installation = AppInstallation::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('uninstallApp', $installation->id)
        ->assertDispatched('toast');

    expect($installation->fresh()->status)->toBe('uninstalled');
});

test('shows the installation detail page', function () {
    $app = App::factory()->create(['name' => 'Detail App']);
    $installation = AppInstallation::factory()->create([
        'store_id' => $this->store->id,
        'app_id' => $app->id,
        'scopes_json' => ['read-products'],
    ]);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get("/admin/apps/{$installation->id}")
        ->assertOk()
        ->assertSee('Detail App')
        ->assertSee('read-products')
        ->assertSee('Webhook subscriptions');
});

test('uninstalls an app from the detail page', function () {
    $installation = AppInstallation::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Show::class, ['installation' => $installation])
        ->call('uninstallApp')
        ->assertRedirectToRoute('admin.apps.index');

    expect($installation->fresh()->status)->toBe('uninstalled');
});

test('installations are scoped to the current store', function () {
    $other = $this->createStore();
    $otherInstallation = AppInstallation::factory()->create(['store_id' => $other->id]);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get("/admin/apps/{$otherInstallation->id}")
        ->assertNotFound();
});

test('enforces the manage-apps gate', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');

    $this->actingAs($staff)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/apps')
        ->assertForbidden();
});
