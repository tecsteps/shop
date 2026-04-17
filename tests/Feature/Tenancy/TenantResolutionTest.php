<?php

use App\Http\Middleware\ResolveStore;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    Route::get('/__resolve_probe', function () {
        /** @var Store $store */
        $store = app('current_store');

        return response()->json([
            'id' => $store->id,
            'handle' => $store->handle,
        ]);
    })->middleware(ResolveStore::class.':storefront');
});

it('resolves store from hostname via store_domains', function (): void {
    $store = Store::factory()->create();
    StoreDomain::factory()->for($store)->create(['hostname' => 'shop.example.test']);

    $response = $this->get('http://shop.example.test/__resolve_probe');

    $response->assertOk()
        ->assertJson([
            'id' => $store->id,
            'handle' => $store->handle,
        ]);
});

it('returns 404 for unknown hostnames', function (): void {
    $response = $this->get('http://unknown.example.test/__resolve_probe');

    $response->assertNotFound();
});

it('returns 503 for suspended stores', function (): void {
    $store = Store::factory()->suspended()->create();
    StoreDomain::factory()->for($store)->create(['hostname' => 'suspended.example.test']);

    $response = $this->get('http://suspended.example.test/__resolve_probe');

    $response->assertStatus(503);
});

it('caches hostname lookup', function (): void {
    $store = Store::factory()->create();
    StoreDomain::factory()->for($store)->create(['hostname' => 'cached.example.test']);

    $this->get('http://cached.example.test/__resolve_probe')->assertOk();

    $cached = Cache::get('store_domain:cached.example.test');

    expect($cached)->toBe($store->id);
});
