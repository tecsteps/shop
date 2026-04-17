<?php

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a theme via factory', function (): void {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    expect($theme->exists)->toBeTrue()
        ->and($theme->status)->toBe(ThemeStatus::Draft)
        ->and($theme->store_id)->toBe($this->store->id);
});

it('publishes a theme and sets published_at', function (): void {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]);

    $theme->update([
        'status' => ThemeStatus::Published,
        'published_at' => now(),
    ]);

    $theme->refresh();

    expect($theme->status)->toBe(ThemeStatus::Published)
        ->and($theme->published_at)->not->toBeNull();
});

it('scopes themes to the current store', function (): void {
    $storeA = $this->store;
    $storeB = Store::factory()->create();

    Theme::factory()->create(['store_id' => $storeA->id]);

    app()->instance('current_store', $storeB);
    Theme::factory()->create(['store_id' => $storeB->id]);

    expect(Theme::count())->toBe(1)
        ->and(Theme::first()->store_id)->toBe($storeB->id);

    app()->instance('current_store', $storeA);
    expect(Theme::count())->toBe(1)
        ->and(Theme::first()->store_id)->toBe($storeA->id);
});

it('rejects invalid status values at the database level', function (): void {
    expect(fn () => \DB::table('themes')->insert([
        'store_id' => $this->store->id,
        'name' => 'Broken',
        'status' => 'garbage',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(Exception::class);
});
