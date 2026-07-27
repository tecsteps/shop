<?php

use App\Enums\PageStatus;
use App\Livewire\Admin\Pages\Form;
use App\Livewire\Admin\Pages\Index;
use App\Models\Page;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('lists pages', function () {
    Page::factory()->create(['store_id' => $this->store->id, 'title' => 'About Us']);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/pages')
        ->assertOk()
        ->assertSee('About Us');
});

test('creates a page via admin form', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('title', 'About Us')
        ->set('bodyHtml', '<p>Our story</p>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $page = Page::query()->where('title', 'About Us')->sole();

    expect($page->handle)->toBe('about-us')
        ->and($page->status)->toBe(PageStatus::Draft)
        ->and($page->published_at)->toBeNull();
});

test('publishing a page sets published_at', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('title', 'Terms')
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()->where('title', 'Terms')->sole();

    expect($page->status)->toBe(PageStatus::Published)
        ->and($page->published_at)->not->toBeNull();
});

test('sanitizes the page body on save', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('title', 'Contact')
        ->set('bodyHtml', '<p>Hello</p><script>alert("xss")</script>')
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::query()->where('title', 'Contact')->sole();

    expect($page->body_html)->toBe('<p>Hello</p>')
        ->and($page->body_html)->not->toContain('<script>');
});

test('edits a page via admin form', function () {
    $page = Page::factory()->create(['store_id' => $this->store->id, 'title' => 'Old Title']);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class, ['page' => $page])
        ->assertSet('title', 'Old Title')
        ->set('title', 'New Title')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect($page->refresh()->title)->toBe('New Title');
});

test('deletes a page', function () {
    $page = Page::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('confirmDelete', $page->id)
        ->call('delete')
        ->assertDispatched('toast');

    $this->assertDatabaseMissing('pages', ['id' => $page->id]);
});

test('staff cannot delete pages', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');
    $page = Page::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($staff);
    Livewire::test(Index::class)
        ->call('confirmDelete', $page->id)
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('pages', ['id' => $page->id]);
});
