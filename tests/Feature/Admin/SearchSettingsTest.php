<?php

use App\Livewire\Admin\Search\Settings as SearchSettingsComponent;
use App\Models\Product;
use App\Models\SearchSettings;
use App\Models\Store;
use App\Models\User;
use App\Services\SearchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminSearchSettingsStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminSearchSettingsUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

test('admin search settings route renders for store admins', function (): void {
    $this->actingAs(adminSearchSettingsUser())
        ->get('/admin/search/settings')
        ->assertSuccessful()
        ->assertSee('Search settings')
        ->assertSee('Synonyms');
});

test('admin search settings saves synonyms and stop words', function (): void {
    $store = adminSearchSettingsStore();
    app()->instance('current_store', $store);

    Livewire::actingAs(adminSearchSettingsUser())
        ->test(SearchSettingsComponent::class)
        ->set('synonymGroups', ['hoodie, sweatshirt, pullover', 'tee, t-shirt'])
        ->set('stopWords', 'the, and, for')
        ->call('save')
        ->assertSee('Search settings saved');

    $settings = SearchSettings::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();

    expect($settings->synonyms_json)->toBe([
        ['hoodie', 'sweatshirt', 'pullover'],
        ['tee', 't-shirt'],
    ])->and($settings->stop_words_json)->toBe(['the', 'and', 'for']);
});

test('admin search settings can rebuild the search index', function (): void {
    $store = adminSearchSettingsStore();
    app()->instance('current_store', $store);

    $product = Product::factory()
        ->for($store)
        ->withDefaultVariant(2999)
        ->create([
            'title' => 'Reindex Search Jacket',
            'handle' => 'reindex-search-jacket',
        ]);

    DB::table('products_fts')->where('product_id', $product->getKey())->delete();

    expect(app(SearchService::class)->search($store, 'reindex jacket', [], 12)->total())->toBe(0);

    Livewire::actingAs(adminSearchSettingsUser())
        ->test(SearchSettingsComponent::class)
        ->call('triggerReindex')
        ->assertSee('Search index rebuilt');

    expect(app(SearchService::class)->search($store, 'reindex jacket', [], 12)->total())->toBe(1);
});
