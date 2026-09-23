<?php

use App\Jobs\ReindexStoreProducts;
use App\Livewire\Admin\Search\Settings;
use App\Models\Product;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('saves synonym groups and stop words for the current store', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    Livewire::actingAs($user)
        ->test(Settings::class)
        ->set('synonymGroups', ['tee, t-shirt, tshirt', ''])
        ->set('stopWords', 'the, a, ARE')
        ->call('save')
        ->assertHasNoErrors();

    $settings = DB::table('search_settings')->where('store_id', $store->id)->first();
    expect(json_decode($settings->synonyms_json, true))->toBe([['tee', 't-shirt', 'tshirt']])
        ->and(json_decode($settings->stop_words_json, true))->toBe(['the', 'a', 'are']);
});

it('uses synonyms and stop words when building FTS expressions', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $tee = Product::factory()->create(['store_id' => $store->id, 'title' => 'Lightweight Tee']);
    $shirt = Product::factory()->create(['store_id' => $store->id, 'title' => 'Cotton T-Shirt']);
    DB::table('search_settings')->insert([
        'store_id' => $store->id,
        'synonyms_json' => json_encode([['tee', 't-shirt']], JSON_THROW_ON_ERROR),
        'stop_words_json' => json_encode(['the'], JSON_THROW_ON_ERROR),
    ]);

    $search = app(SearchService::class);
    $expression = $search->fullTextExpression($store->id, 'the tee');
    $productIds = $search->rankedProductIds($store->id, $expression);

    expect($expression)->toContain('tee')
        ->and(array_map('intval', $productIds))->toContain($tee->id)
        ->and(array_map('intval', $productIds))->toContain($shirt->id)
        ->and($search->fullTextExpression($store->id, 'the'))->toBe('');
});

it('queues a store-scoped reindex and rejects duplicate in-progress requests', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    Queue::fake();

    $component = Livewire::actingAs($user)->test(Settings::class)->call('reindex')->assertHasNoErrors();
    $component->call('reindex')->assertHasErrors('reindex');

    Queue::assertPushed(ReindexStoreProducts::class, fn (ReindexStoreProducts $job): bool => $job->storeId === $store->id);
    expect(DB::table('search_settings')->where('store_id', $store->id)->value('index_status'))->toBe('queued');
});

it('rebuilds only the requested store index and tracks job progress', function () {
    $firstStore = shopStore();
    $secondStore = shopStore('second-search.test');
    $firstProduct = Product::factory()->create(['store_id' => $firstStore->id, 'title' => 'Reindex this wool coat']);
    $secondProduct = Product::factory()->create(['store_id' => $secondStore->id, 'title' => 'Keep this cotton bag']);
    DB::table('products_fts')->where('product_id', (string) $firstProduct->id)->delete();

    (new ReindexStoreProducts($firstStore->id))->handle();

    $firstIndexedTitle = DB::table('products_fts')->where('product_id', (string) $firstProduct->id)->value('title');
    $secondIndexedTitle = DB::table('products_fts')->where('product_id', (string) $secondProduct->id)->value('title');
    $settings = DB::table('search_settings')->where('store_id', $firstStore->id)->first();
    expect($firstIndexedTitle)->toBe('Reindex this wool coat')
        ->and($secondIndexedTitle)->toBe('Keep this cotton bag')
        ->and($settings->index_status)->toBe('ready')
        ->and($settings->documents_count)->toBe(1)
        ->and($settings->pending_updates)->toBe(0)
        ->and($settings->last_reindex_at)->not->toBeNull();
});
