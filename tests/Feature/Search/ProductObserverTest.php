<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
    $this->service = app(SearchService::class);
});

test('syncs product to FTS5 index on create', function () {
    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Observer Created Product',
        'handle' => 'observer-created-product',
        'status' => ProductStatus::Active,
        'description_html' => '<p>Testing observer sync</p>',
        'vendor' => 'ObserverBrand',
        'product_type' => 'Electronics',
        'tags' => ['new', 'featured'],
        'published_at' => now(),
    ]);

    $ftsRow = DB::table('products_fts')
        ->where('product_id', $product->id)
        ->first();

    expect($ftsRow)->not->toBeNull();
    expect($ftsRow->title)->toBe('Observer Created Product');
    expect($ftsRow->vendor)->toBe('ObserverBrand');
});

test('updates FTS5 index on product update', function () {
    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Original Title',
        'handle' => 'original-title',
        'status' => ProductStatus::Active,
        'tags' => [],
        'published_at' => now(),
    ]);

    $product->update(['title' => 'Updated Title']);

    $ftsRow = DB::table('products_fts')
        ->where('product_id', $product->id)
        ->first();

    expect($ftsRow)->not->toBeNull();
    expect($ftsRow->title)->toBe('Updated Title');
});

test('removes product from FTS5 index on delete', function () {
    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Deletable Product',
        'handle' => 'deletable-product',
        'status' => ProductStatus::Draft,
        'tags' => [],
    ]);

    $productId = $product->id;

    $ftsBeforeDelete = DB::table('products_fts')
        ->where('product_id', $productId)
        ->exists();

    expect($ftsBeforeDelete)->toBeTrue();

    $product->delete();

    $ftsAfterDelete = DB::table('products_fts')
        ->where('product_id', $productId)
        ->exists();

    expect($ftsAfterDelete)->toBeFalse();
});

test('sync strips HTML tags from description', function () {
    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'HTML Product',
        'handle' => 'html-product',
        'status' => ProductStatus::Active,
        'description_html' => '<p>A <strong>bold</strong> description with <a href="#">links</a></p>',
        'tags' => [],
        'published_at' => now(),
    ]);

    $ftsRow = DB::table('products_fts')
        ->where('product_id', $product->id)
        ->first();

    expect($ftsRow->description)->not->toContain('<p>');
    expect($ftsRow->description)->not->toContain('<strong>');
    expect($ftsRow->description)->toContain('bold');
    expect($ftsRow->description)->toContain('description');
});

test('sync converts tags array to space-separated string', function () {
    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Tagged Product',
        'handle' => 'tagged-product',
        'status' => ProductStatus::Active,
        'tags' => ['summer', 'sale', 'new'],
        'published_at' => now(),
    ]);

    $ftsRow = DB::table('products_fts')
        ->where('product_id', $product->id)
        ->first();

    expect($ftsRow->tags)->toBe('summer sale new');
});

test('sync handles null vendor and product type', function () {
    $product = Product::withoutGlobalScopes()->create([
        'store_id' => $this->store->id,
        'title' => 'Minimal Product',
        'handle' => 'minimal-product',
        'status' => ProductStatus::Active,
        'vendor' => null,
        'product_type' => null,
        'tags' => [],
        'published_at' => now(),
    ]);

    $ftsRow = DB::table('products_fts')
        ->where('product_id', $product->id)
        ->first();

    expect($ftsRow)->not->toBeNull();
    expect($ftsRow->vendor)->toBe('');
    expect($ftsRow->product_type)->toBe('');
});
