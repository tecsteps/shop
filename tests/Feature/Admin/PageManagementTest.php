<?php

use App\Enums\PageStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\Page;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists pages with search', function () {
    Page::factory()->for($this->store)->create(['title' => 'About Us']);
    Page::factory()->for($this->store)->create(['title' => 'Shipping Policy']);

    actingAsAdmin($this->user)
        ->get('/admin/pages')
        ->assertOk()
        ->assertSee('About Us')
        ->assertSee('Shipping Policy');

    $component = Livewire::test(PagesIndex::class)->set('search', 'About');

    expect($component->instance()->pages()->total())->toBe(1);
});

it('creates a draft page', function () {
    actingAsAdmin($this->user);

    Livewire::test(PageForm::class)
        ->set('title', 'Returns Policy')
        ->set('bodyHtml', '<p>You can return items within 30 days.</p>')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pages', [
        'store_id' => $this->store->getKey(),
        'title' => 'Returns Policy',
        'handle' => 'returns-policy',
        'status' => 'draft',
    ]);
});

it('publishes a page and backfills the published date', function () {
    $page = Page::factory()->draft()->for($this->store)->create();

    actingAsAdmin($this->user);

    Livewire::test(PageForm::class, ['pageId' => $page->getKey()])
        ->set('status', 'published')
        ->call('save')
        ->assertHasNoErrors();

    $page->refresh();

    expect($page->status)->toBe(PageStatus::Published);
    expect($page->published_at)->not->toBeNull();
});

it('edits a page', function () {
    $page = Page::factory()->for($this->store)->create(['title' => 'Old Page Title']);

    actingAsAdmin($this->user);

    Livewire::test(PageForm::class, ['pageId' => $page->getKey()])
        ->set('title', 'New Page Title')
        ->set('bodyHtml', '<p>Updated body.</p>')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pages', [
        'id' => $page->getKey(),
        'title' => 'New Page Title',
        'body_html' => '<p>Updated body.</p>',
    ]);
});

it('validates handle uniqueness within store', function () {
    Page::factory()->for($this->store)->create(['handle' => 'about']);

    actingAsAdmin($this->user);

    Livewire::test(PageForm::class)
        ->set('title', 'Another About')
        ->set('handle', 'about')
        ->call('save')
        ->assertHasErrors(['handle']);
});

it('deletes a page', function () {
    $page = Page::factory()->for($this->store)->create();

    actingAsAdmin($this->user);

    Livewire::test(PageForm::class, ['pageId' => $page->getKey()])
        ->call('deletePage');

    $this->assertDatabaseMissing('pages', ['id' => $page->getKey()]);
});

it('restricts page deletion to owner and admin roles', function () {
    $page = Page::factory()->for($this->store)->create();

    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store);

    Livewire::test(PageForm::class, ['pageId' => $page->getKey()])
        ->set('title', 'Staff Edited Title')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(PageForm::class, ['pageId' => $page->getKey()])
        ->call('deletePage')
        ->assertForbidden();

    $this->assertDatabaseHas('pages', ['id' => $page->getKey()]);
});
