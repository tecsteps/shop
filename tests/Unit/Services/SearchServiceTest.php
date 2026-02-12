<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Support\Facades\DB;

it('reindexes products and supports query, search, and autocomplete basics', function () {
    $store = Store::factory()->create();
    $otherStore = Store::factory()->create();

    $matching = Product::factory()
        ->for($store)
        ->create([
            'title' => 'Alpha Jacket',
            'handle' => 'alpha-jacket',
            'status' => 'active',
            'published_at' => now(),
        ]);

    Product::factory()
        ->for($store)
        ->draft()
        ->create([
            'title' => 'Alpha Draft',
            'handle' => 'alpha-draft',
        ]);

    Product::factory()
        ->for($otherStore)
        ->create([
            'title' => 'Alpha Other Store',
            'handle' => 'alpha-other-store',
        ]);

    $service = app(SearchService::class);
    $service->reindex($store);

    expect((int) DB::table('products_fts')->where('store_id', $store->id)->count())->toBe(2);

    $queried = $service->query($store, 'alpha', 10);

    expect($queried->pluck('id')->all())->toBe([$matching->id]);

    $autocomplete = $service->autocomplete($store, 'alp', 10);

    expect($autocomplete)->toHaveCount(1)
        ->and($autocomplete->first()['id'])->toBe($matching->id)
        ->and($autocomplete->first()['handle'])->toBe('alpha-jacket');

    $searched = $service->search($store, 'alpha', [], 10);

    expect($searched->total())->toBe(1)
        ->and(SearchQuery::query()
            ->where('store_id', $store->id)
            ->where('query', 'alpha')
            ->exists())->toBeTrue();
});
