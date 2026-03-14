<?php

use App\Livewire\Admin\Pages\Form;
use App\Livewire\Admin\Pages\Index;
use App\Models\Page;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('requires authentication for pages', function () {
    $this->get(route('admin.pages.index'))
        ->assertRedirect(route('admin.login'));
});

it('renders the pages index', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.pages.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('lists existing pages', function () {
    Page::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'About Us',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->assertSee('About Us');
});

it('searches pages by title', function () {
    Page::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'About Us',
    ]);
    Page::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Contact',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Index::class)
        ->set('search', 'About')
        ->assertSee('About Us')
        ->assertDontSee('Contact');
});

it('renders the create page form', function () {
    $this->actingAs($this->ctx['user']);

    $this->get(route('admin.pages.create'))
        ->assertOk()
        ->assertSeeLivewire(Form::class);
});

it('creates a new page', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('title', 'New Page')
        ->set('handle', 'new-page')
        ->set('bodyHtml', '<p>Hello</p>')
        ->set('status', 'draft')
        ->call('save')
        ->assertDispatched('toast');

    expect(Page::where('title', 'New Page')->exists())->toBeTrue();
});

it('auto-generates handle from title on create', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('title', 'My Great Page')
        ->assertSet('handle', 'my-great-page');
});

it('edits an existing page', function () {
    $page = Page::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Original Title',
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class, ['page' => $page])
        ->assertSet('title', 'Original Title')
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertDispatched('toast');

    $page->refresh();
    expect($page->title)->toBe('Updated Title');
});

it('validates title is required', function () {
    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class)
        ->set('title', '')
        ->set('handle', 'test')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

it('deletes a page', function () {
    $page = Page::factory()->create([
        'store_id' => $this->ctx['store']->id,
    ]);

    Livewire::actingAs($this->ctx['user'])
        ->test(Form::class, ['page' => $page])
        ->call('deletePage')
        ->assertDispatched('toast');

    expect(Page::find($page->id))->toBeNull();
});
