<?php

use App\Enums\NavigationItemType;
use App\Enums\PageStatus;
use App\Enums\StoreUserRole;
use App\Enums\ThemeStatus;
use App\Livewire\Admin\Navigation\Index as AdminNavigationIndex;
use App\Livewire\Admin\Pages\Form as AdminPageForm;
use App\Livewire\Admin\Pages\Index as AdminPagesIndex;
use App\Livewire\Admin\Themes\Editor as AdminThemeEditor;
use App\Livewire\Admin\Themes\Index as AdminThemesIndex;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminContentStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function adminContentUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

function adminContentUserWithRole(Store $store, StoreUserRole $role): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->getKey(), [
        'role' => $role->value,
        'created_at' => now(),
    ]);

    return $user;
}

test('content routes render store scoped pages navigation and themes', function (): void {
    $store = adminContentStore();
    $user = adminContentUser();
    Page::factory()->create([
        'store_id' => Store::factory()->create()->getKey(),
        'title' => 'Other Store Page',
    ]);
    $theme = Theme::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();

    $this->get('/admin/pages')->assertRedirect('/admin/login');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/pages')
        ->assertSuccessful()
        ->assertSee('About')
        ->assertDontSee('Other Store Page');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/pages/create')
        ->assertSuccessful()
        ->assertSee('Create page');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/navigation')
        ->assertSuccessful()
        ->assertSee('Main Menu');

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/themes')
        ->assertSuccessful()
        ->assertSee($theme->name);

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/themes/'.$theme->getKey().'/editor')
        ->assertSuccessful()
        ->assertSee('Sections');
});

test('admin pages can be created searched edited and deleted', function (): void {
    $store = adminContentStore();
    $user = adminContentUser();

    Livewire::actingAs($user)
        ->test(AdminPageForm::class)
        ->set('title', 'Sizing Guide')
        ->set('handle', 'sizing-guide')
        ->set('bodyHtml', '<p>Measure twice.</p>')
        ->set('status', PageStatus::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'sizing-guide')
        ->firstOrFail();

    expect($page->status)->toBe(PageStatus::Published)
        ->and($page->published_at)->not->toBeNull();

    Livewire::actingAs($user)
        ->test(AdminPagesIndex::class)
        ->set('search', 'sizing')
        ->assertSee('Sizing Guide')
        ->assertDontSee('About');

    Livewire::actingAs($user)
        ->test(AdminPageForm::class, ['page' => $page])
        ->set('title', 'Size Guide')
        ->set('status', PageStatus::Draft->value)
        ->call('save')
        ->assertHasNoErrors();

    expect($page->refresh()->title)->toBe('Size Guide')
        ->and($page->status)->toBe(PageStatus::Draft);

    Livewire::actingAs($user)
        ->test(AdminPageForm::class, ['page' => $page])
        ->call('deletePage')
        ->assertRedirect(route('admin.pages.index', absolute: false));

    expect(Page::withoutGlobalScopes()->whereKey($page->getKey())->exists())->toBeFalse();
});

test('navigation menu items can be edited and persisted in order', function (): void {
    $store = adminContentStore();
    $user = adminContentUser();
    $menu = NavigationMenu::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'main-menu')
        ->firstOrFail();
    $initialItemCount = $menu->items()->count();

    Livewire::actingAs($user)
        ->test(AdminNavigationIndex::class)
        ->call('selectMenu', $menu->getKey())
        ->set('itemLabel', 'Lookbook')
        ->set('itemType', NavigationItemType::Link->value)
        ->set('itemUrl', '/lookbook')
        ->call('saveItem')
        ->call('moveItemUp', $initialItemCount)
        ->call('saveMenu')
        ->assertHasNoErrors();

    $items = NavigationItem::withoutGlobalScopes()
        ->where('menu_id', $menu->getKey())
        ->orderBy('position')
        ->get();

    expect($items->pluck('position')->all())->toBe(range(0, $items->count() - 1))
        ->and($items->pluck('label'))->toContain('Lookbook');
});

test('themes can be duplicated edited and published', function (): void {
    $store = adminContentStore();
    $user = adminContentUser();
    $published = Theme::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();

    Livewire::actingAs($user)
        ->test(AdminThemesIndex::class)
        ->call('duplicateTheme', $published->getKey())
        ->assertHasNoErrors();

    $copy = Theme::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('name', $published->name.' Copy')
        ->firstOrFail();
    $publishedFile = $published->files()->withoutGlobalScopes()->firstOrFail();
    $copyFile = $copy->files()->withoutGlobalScopes()->where('path', $publishedFile->path)->firstOrFail();

    expect($copyFile->storage_key)->not->toBe($publishedFile->storage_key)
        ->and(Storage::disk('local')->get($copyFile->storage_key))->toBe(Storage::disk('local')->get($publishedFile->storage_key));

    Livewire::actingAs($user)
        ->test(AdminThemeEditor::class, ['theme' => $copy])
        ->set('settings.home.hero.heading', 'New Hero')
        ->call('save')
        ->call('publish')
        ->assertHasNoErrors();

    expect(ThemeSettings::withoutGlobalScopes()->where('theme_id', $copy->getKey())->first()?->settings_json['home']['hero']['heading'])->toBe('New Hero')
        ->and($copy->refresh()->status)->toBe(ThemeStatus::Published)
        ->and($published->refresh()->status)->toBe(ThemeStatus::Draft)
        ->and(Theme::withoutGlobalScopes()->where('store_id', $store->getKey())->where('status', ThemeStatus::Published)->count())->toBe(1);
});

test('theme editor saves file contents and metadata', function (): void {
    $store = adminContentStore();
    $user = adminContentUser();
    $theme = Theme::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();
    $file = $theme->files()->withoutGlobalScopes()->where('path', 'sections/hero.blade.php')->firstOrFail();

    Storage::disk('local')->put($file->storage_key, 'Original theme file');

    Livewire::actingAs($user)
        ->test(AdminThemeEditor::class, ['theme' => $theme])
        ->call('selectFile', $file->getKey())
        ->assertSet('selectedFileId', $file->getKey())
        ->assertSet('fileContents', 'Original theme file')
        ->set('fileContents', '<section>Edited hero file</section>')
        ->call('saveFile')
        ->assertHasNoErrors()
        ->assertSee('Theme file saved');

    expect(Storage::disk('local')->get($file->storage_key))->toBe('<section>Edited hero file</section>')
        ->and($file->refresh()->sha256)->toBe(hash('sha256', '<section>Edited hero file</section>'))
        ->and($file->byte_size)->toBe(strlen('<section>Edited hero file</section>'));
});

test('content management honors store roles and store scoping', function (): void {
    $store = adminContentStore();
    $support = adminContentUserWithRole($store, StoreUserRole::Support);
    $staff = adminContentUserWithRole($store, StoreUserRole::Staff);
    $otherPage = Page::factory()->create([
        'store_id' => Store::factory()->create()->getKey(),
    ]);
    $theme = Theme::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();

    Livewire::actingAs($support)
        ->test(AdminPageForm::class)
        ->assertStatus(403);

    Livewire::actingAs($staff)
        ->test(AdminThemesIndex::class)
        ->assertStatus(403);

    Livewire::actingAs($staff)
        ->test(AdminNavigationIndex::class)
        ->assertStatus(403);

    Livewire::actingAs(adminContentUser())
        ->test(AdminPageForm::class, ['page' => $otherPage])
        ->assertStatus(404);

    Livewire::actingAs(adminContentUser())
        ->test(AdminThemeEditor::class, ['theme' => $theme])
        ->assertSuccessful();
});
