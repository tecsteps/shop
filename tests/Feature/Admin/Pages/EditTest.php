<?php

use App\Livewire\Admin\Pages\Edit;
use App\Models\Page;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function pagesEditAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the edit page with pre-populated data', function () {
    [$user, $store] = pagesEditAdmin();

    $page = Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'About Us',
        'handle' => 'about-us',
        'status' => 'published',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['page' => $page])
        ->assertSet('title', 'About Us')
        ->assertSet('handle', 'about-us')
        ->assertSet('status', 'published')
        ->assertSuccessful();
});

test('it updates page fields', function () {
    [$user, $store] = pagesEditAdmin();

    $page = Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'Original Title',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['page' => $page])
        ->set('title', 'Updated Title')
        ->set('bodyHtml', '<p>Updated content</p>')
        ->call('save')
        ->assertDispatched('toast');

    $page->refresh();

    expect($page->title)->toBe('Updated Title')
        ->and($page->body_html)->toBe('<p>Updated content</p>');
});

test('it sets published_at when status changes to published', function () {
    [$user, $store] = pagesEditAdmin();

    $page = Page::factory()->draft()->create([
        'store_id' => $store->id,
    ]);

    expect($page->published_at)->toBeNull();

    Livewire::actingAs($user)
        ->test(Edit::class, ['page' => $page])
        ->set('status', 'published')
        ->call('save')
        ->assertDispatched('toast');

    $page->refresh();

    expect($page->status->value)->toBe('published')
        ->and($page->published_at)->not->toBeNull();
});

test('it preserves published_at when page was already published', function () {
    [$user, $store] = pagesEditAdmin();

    $publishedAt = now()->subDays(5);

    $page = Page::factory()->create([
        'store_id' => $store->id,
        'status' => 'published',
        'published_at' => $publishedAt,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['page' => $page])
        ->set('title', 'Same Status')
        ->call('save')
        ->assertDispatched('toast');

    $page->refresh();

    expect($page->published_at->format('Y-m-d'))->toBe($publishedAt->format('Y-m-d'));
});

test('it validates required fields on update', function () {
    [$user, $store] = pagesEditAdmin();

    $page = Page::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['page' => $page])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('it aborts if page belongs to a different store', function () {
    [$user, $store] = pagesEditAdmin();

    $otherStore = Store::factory()->create();
    $page = Page::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['page' => $page])
        ->assertNotFound();
});
