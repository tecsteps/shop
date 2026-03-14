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

it('renders search settings placeholder', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.search.settings'))
        ->assertOk()
        ->assertSeeLivewire(SearchSettings::class)
        ->assertSee('Search settings coming soon');
});

it('requires authentication for apps', function () {
    $this->get(route('admin.apps.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders apps placeholder', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.apps.index'))
        ->assertOk()
        ->assertSeeLivewire(AppsIndex::class)
        ->assertSee('Apps marketplace coming soon');
});

it('requires authentication for developers', function () {
    $this->get(route('admin.developers.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders developers placeholder', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.developers.index'))
        ->assertOk()
        ->assertSeeLivewire(DevelopersIndex::class)
        ->assertSee('Developer tools coming soon');
});
