<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('fts search returns published products from only the requested store', function () {
    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();
    $matchingProduct = Product::factory()->for($firstStore)->create([
        'title' => 'Organic Cotton Shirt',
        'vendor' => 'Acme Apparel',
        'tags' => ['organic', 'cotton'],
    ]);
    Product::factory()->for($secondStore)->create(['title' => 'Organic Cotton Shirt']);
    Product::factory()->for($firstStore)->draft()->create(['title' => 'Organic Cotton Draft']);

    $results = app(SearchService::class)->search($firstStore, 'organic cott', ['vendor' => 'Acme Apparel'], 10);

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->is($matchingProduct))->toBeTrue()
        ->and(SearchQuery::withoutGlobalScopes()->sole()->results_count)->toBe(1);
});

test('product observer keeps the fts index synchronized on update and delete', function () {
    $store = Store::factory()->create();
    $product = Product::factory()->for($store)->create(['title' => 'Initial Search Title']);

    expect(DB::table('products_fts')->where('product_id', $product->id)->count())->toBe(1);

    $product->update(['title' => 'Updated Search Title']);

    expect(app(SearchService::class)->search($store, 'updated')->total())->toBe(1);

    $product->delete();

    expect(DB::table('products_fts')->where('product_id', $product->id)->count())->toBe(0);
});
