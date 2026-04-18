<?php

use App\Support\HandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $context = $this->createStoreContext();
    $this->store = $context['store'];
});

it('generates a slug from title', function (): void {
    $handle = HandleGenerator::generate('My Amazing Product', 'products', $this->store->id);
    expect($handle)->toBe('my-amazing-product');
});

it('appends suffix on collision', function (): void {
    \DB::table('products')->insert([
        'store_id' => $this->store->id,
        'title' => 'T-Shirt',
        'handle' => 't-shirt',
        'status' => 'draft',
        'tags' => '[]',
    ]);

    $handle = HandleGenerator::generate('T-Shirt', 'products', $this->store->id);
    expect($handle)->toBe('t-shirt-1');
});

it('increments suffix on multiple collisions', function (): void {
    foreach (['t-shirt', 't-shirt-1'] as $existing) {
        \DB::table('products')->insert([
            'store_id' => $this->store->id,
            'title' => 'T-Shirt',
            'handle' => $existing,
            'status' => 'draft',
            'tags' => '[]',
        ]);
    }

    $handle = HandleGenerator::generate('T-Shirt', 'products', $this->store->id);
    expect($handle)->toBe('t-shirt-2');
});

it('handles special characters in the title', function (): void {
    $handle = HandleGenerator::generate("Loewe's Fall/Winter 2026", 'products', $this->store->id);
    expect($handle)->toMatch('/^[a-z0-9-]+$/');
    expect($handle)->toContain('loewe');
    expect($handle)->toContain('fall');
    expect($handle)->toContain('2026');
});

it('excludes current record id from collision check', function (): void {
    $id = \DB::table('products')->insertGetId([
        'store_id' => $this->store->id,
        'title' => 'T-Shirt',
        'handle' => 't-shirt',
        'status' => 'draft',
        'tags' => '[]',
    ]);

    $handle = HandleGenerator::generate('T-Shirt', 'products', $this->store->id, $id);
    expect($handle)->toBe('t-shirt');
});

it('scopes uniqueness check to store', function (): void {
    $otherContext = $this->createStoreContext(['hostname' => 'other-store.test']);
    $otherStore = $otherContext['store'];

    \DB::table('products')->insert([
        'store_id' => $otherStore->id,
        'title' => 'T-Shirt',
        'handle' => 't-shirt',
        'status' => 'draft',
        'tags' => '[]',
    ]);

    $handle = HandleGenerator::generate('T-Shirt', 'products', $this->store->id);
    expect($handle)->toBe('t-shirt');
});
