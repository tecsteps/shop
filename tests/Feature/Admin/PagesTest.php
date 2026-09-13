<?php

use App\Enums\PageStatus;
use App\Livewire\Admin\Pages\Form;
use App\Models\Page;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists pages scoped to the current store', function () {
    Page::factory()->create(['store_id' => $this->store->getKey(), 'title' => 'About Us']);

    $this->get(route('admin.pages.index'))
        ->assertOk()
        ->assertSee('About Us');
});

it('creates a page', function () {
    Livewire::test(Form::class)
        ->set('title', 'Shipping Info')
        ->set('bodyHtml', '<p>We ship worldwide.</p>')
        ->set('status', 'published')
        ->call('save')
        ->assertRedirect(route('admin.pages.index'));

    $page = Page::query()->where('store_id', $this->store->getKey())->where('handle', 'shipping-info')->firstOrFail();

    expect($page->status)->toBe(PageStatus::Published)
        ->and($page->published_at)->not->toBeNull();
});

it('edits a page', function () {
    $page = Page::factory()->create(['store_id' => $this->store->getKey()]);

    Livewire::test(Form::class, ['page' => $page])
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertDispatched('toast');

    expect($page->refresh()->title)->toBe('Updated Title');
});

it('deletes a page', function () {
    $page = Page::factory()->create(['store_id' => $this->store->getKey()]);

    Livewire::test(Form::class, ['page' => $page])
        ->call('deletePage')
        ->assertRedirect(route('admin.pages.index'));

    expect(Page::query()->whereKey($page->getKey())->exists())->toBeFalse();
});

it('validates duplicate handles', function () {
    Page::factory()->create(['store_id' => $this->store->getKey(), 'handle' => 'about']);

    Livewire::test(Form::class)
        ->set('title', 'About')
        ->set('handle', 'about')
        ->set('status', 'draft')
        ->call('save')
        ->assertHasErrors('handle');
});

it('returns 404 for a page from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $page = Page::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->get(route('admin.pages.edit', $page))->assertNotFound();
});
