<?php

use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use App\Livewire\Admin\Search\Settings as SearchSettings;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for search settings', function () {
    $this->get(route('admin.search.settings'))
        ->assertRedirect(route('admin.login'));
});

it('renders search settings page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.search.settings'))
        ->assertOk()
        ->assertSeeLivewire(SearchSettings::class)
        ->assertSee('Search Settings');
});

it('requires authentication for apps', function () {
    $this->get(route('admin.apps.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders apps page', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.apps.index'))
        ->assertOk()
        ->assertSeeLivewire(AppsIndex::class)
        ->assertSee('No apps installed');
});

it('requires authentication for developers', function () {
    $this->get(route('admin.developers.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders developers page with webhook management', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.developers.index'))
        ->assertOk()
        ->assertSeeLivewire(DevelopersIndex::class)
        ->assertSee('Webhook Subscriptions');
});
