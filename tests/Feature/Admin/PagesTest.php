<?php

use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Pages\Index as PageIndex;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a page', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(PageForm::class)
        ->set('title', 'About Us')
        ->set('bodyHtml', '<p>Welcome</p>')
        ->set('status', 'published')
        ->call('save')
        ->assertRedirect(route('admin.pages.index'));

    $page = Page::where('title', 'About Us')->first();
    expect($page)->not->toBeNull()
        ->and($page->store_id)->toBe($store->id)
        ->and($page->handle)->toBe('about-us')
        ->and($page->status->value)->toBe('published');
});

it('edits a page', function (): void {
    [$user, $store] = loginAsAdmin();

    $page = Page::factory()->create([
        'store_id' => $store->id,
        'title' => 'Draft Title',
        'status' => 'draft',
    ]);

    Livewire::test(PageForm::class, ['page' => $page])
        ->set('title', 'New Title')
        ->call('save')
        ->assertRedirect(route('admin.pages.index'));

    expect($page->fresh()->title)->toBe('New Title');
});

it('deletes a page', function (): void {
    [$user, $store] = loginAsAdmin();

    $page = Page::factory()->create(['store_id' => $store->id]);

    Livewire::test(PageIndex::class)
        ->call('delete', $page->id);

    expect(Page::find($page->id))->toBeNull();
});
