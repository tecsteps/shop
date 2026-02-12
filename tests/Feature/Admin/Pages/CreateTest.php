<?php

use App\Livewire\Admin\Pages\Create;
use App\Models\Page;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function pagesCreateAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the create page form', function () {
    [$user, $store] = pagesCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->assertSee('Create page')
        ->assertSuccessful();
});

test('it auto-generates handle from title', function () {
    [$user, $store] = pagesCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'About Our Company')
        ->assertSet('handle', 'about-our-company');
});

test('it creates a draft page', function () {
    [$user, $store] = pagesCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'Privacy Policy')
        ->set('bodyHtml', '<p>Our privacy policy...</p>')
        ->set('status', 'draft')
        ->call('save')
        ->assertDispatched('toast')
        ->assertRedirect();

    $page = Page::where('title', 'Privacy Policy')->first();

    expect($page)->not->toBeNull()
        ->and($page->store_id)->toBe($store->id)
        ->and($page->handle)->toBe('privacy-policy')
        ->and($page->body_html)->toBe('<p>Our privacy policy...</p>')
        ->and($page->status->value)->toBe('draft')
        ->and($page->published_at)->toBeNull();
});

test('it creates a published page with published_at timestamp', function () {
    [$user, $store] = pagesCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'Published Page')
        ->set('status', 'published')
        ->call('save')
        ->assertRedirect();

    $page = Page::where('title', 'Published Page')->first();

    expect($page->status->value)->toBe('published')
        ->and($page->published_at)->not->toBeNull();
});

test('it validates required fields', function () {
    [$user, $store] = pagesCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});
