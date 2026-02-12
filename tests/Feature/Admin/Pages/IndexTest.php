<?php

use App\Livewire\Admin\Pages\Index;
use App\Models\Page;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function pagesAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the pages index', function () {
    [$user, $store] = pagesAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Pages')
        ->assertSuccessful();
});

test('it lists pages for the current store', function () {
    [$user, $store] = pagesAdmin();

    Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'About Us',
    ]);

    Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'Contact',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('About Us')
        ->assertSee('Contact');
});

test('it does not show pages from other stores', function () {
    [$user, $store] = pagesAdmin();

    $otherStore = Store::factory()->create();

    Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'My Page',
    ]);

    Page::factory()->create([
        'store_id' => $otherStore->id,
        'title' => 'Other Page',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('My Page')
        ->assertDontSee('Other Page');
});

test('it filters pages by search term', function () {
    [$user, $store] = pagesAdmin();

    Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'About Us',
    ]);

    Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'Shipping Policy',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'About')
        ->assertSee('About Us')
        ->assertDontSee('Shipping Policy');
});

test('it filters pages by status', function () {
    [$user, $store] = pagesAdmin();

    Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'Published Page',
        'status' => 'published',
    ]);

    Page::factory()->draft()->create([
        'store_id' => $store->id,
        'title' => 'Draft Page',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('statusFilter', 'published')
        ->assertSee('Published Page')
        ->assertDontSee('Draft Page');
});

test('it deletes a page', function () {
    [$user, $store] = pagesAdmin();

    $page = Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'Delete Me',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('delete', $page->id)
        ->assertDispatched('toast');

    expect(Page::find($page->id))->toBeNull();
});
