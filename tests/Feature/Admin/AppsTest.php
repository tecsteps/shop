<?php

use App\Enums\AppInstallationStatus;
use App\Enums\StoreUserRole;
use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Models\App as AppModel;
use App\Models\AppInstallation;
use App\Models\Organization;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the apps page with installed and available apps', function () {
    $installedApp = AppModel::factory()->create(['name' => 'Loyalty Rewards']);
    AppInstallation::factory()->for($this->store)->for($installedApp)->create();

    AppModel::factory()->create(['name' => 'Shipping Labels Pro']);

    actingAsAdmin($this->user)
        ->get('/admin/apps')
        ->assertOk()
        ->assertSee('Loyalty Rewards')
        ->assertSee('Shipping Labels Pro');
});

it('installs an app from the directory', function () {
    $app = AppModel::factory()->create();

    actingAsAdmin($this->user);

    Livewire::test(AppsIndex::class)
        ->call('installApp', $app->getKey());

    $this->assertDatabaseHas('app_installations', [
        'store_id' => $this->store->getKey(),
        'app_id' => $app->getKey(),
        'status' => 'active',
    ]);
});

it('reactivates an uninstalled app on reinstall', function () {
    $app = AppModel::factory()->create();

    $installation = AppInstallation::factory()
        ->for($this->store)
        ->for($app)
        ->uninstalled()
        ->create();

    actingAsAdmin($this->user);

    Livewire::test(AppsIndex::class)
        ->call('installApp', $app->getKey());

    expect($installation->refresh()->status)->toBe(AppInstallationStatus::Active)
        ->and(AppInstallation::query()->count())->toBe(1);
});

it('uninstalls an app and disables its webhook subscriptions', function () {
    $installation = AppInstallation::factory()->for($this->store)->create();

    $subscription = WebhookSubscription::factory()->for($this->store)->create([
        'app_installation_id' => $installation->getKey(),
    ]);

    actingAsAdmin($this->user);

    Livewire::test(AppsIndex::class)
        ->call('uninstallApp', $installation->getKey());

    expect($installation->refresh()->status)->toBe(AppInstallationStatus::Uninstalled)
        ->and($subscription->refresh()->status)->toBe(WebhookSubscriptionStatus::Disabled);
});

it('shows installed app details with scopes and webhook subscriptions', function () {
    $app = AppModel::factory()->create(['name' => 'Loyalty Rewards']);

    $installation = AppInstallation::factory()->for($this->store)->for($app)->create([
        'scopes_json' => ['read-orders', 'read-customers'],
    ]);

    WebhookSubscription::factory()->for($this->store)->create([
        'app_installation_id' => $installation->getKey(),
        'event_type' => 'order.created',
        'target_url' => 'https://loyalty.example.test/hooks',
    ]);

    actingAsAdmin($this->user)
        ->get('/admin/apps/'.$installation->getKey())
        ->assertOk()
        ->assertSee('Loyalty Rewards')
        ->assertSee('read-orders')
        ->assertSee('read-customers')
        ->assertSee('order.created')
        ->assertSee('https://loyalty.example.test/hooks');
});

it('returns 404 for an installation belonging to another store', function () {
    $otherStore = Store::factory()->for(Organization::factory())->create();

    $foreignInstallation = AppInstallation::factory()->for($otherStore)->create();

    actingAsAdmin($this->user)
        ->get('/admin/apps/'.$foreignInstallation->getKey())
        ->assertNotFound();
});

it('restricts the apps page to owner and admin roles', function () {
    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store)
        ->get('/admin/apps')
        ->assertForbidden();

    $admin = createStoreMember($this->store, StoreUserRole::Admin);

    actingAsAdmin($admin, $this->store)
        ->get('/admin/apps')
        ->assertOk();
});
