<?php

use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates a slug handle from a title', function () {
    $store = Store::factory()->create();
    $generator = app(HandleGenerator::class);

    expect($generator->generate('Classic Cotton T-Shirt', 'products', $store->id))
        ->toBe('classic-cotton-t-shirt');
});

it('appends a numeric suffix when the handle already exists', function () {
    $store = Store::factory()->create();
    Product::factory()->create([
        'store_id' => $store->id,
        'handle' => 'classic-cotton-t-shirt',
    ]);

    $generator = app(HandleGenerator::class);

    expect($generator->generate('Classic Cotton T-Shirt', 'products', $store->id))
        ->toBe('classic-cotton-t-shirt-2');
});
