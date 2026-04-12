<?php

use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
    $this->service = app(ThemeSettingsService::class);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
    Cache::flush();
});

it('returns default settings for a store without a published theme', function (): void {
    $settings = $this->service->forStore($this->store);

    expect($settings)->toMatchArray([
        'colors' => ['primary' => '#111'],
        'announcement' => null,
        'footer_text' => '(c) Shop',
    ]);
});

it('returns theme settings_json for a store with a published theme', function (): void {
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);

    ThemeSettings::create([
        'theme_id' => $theme->id,
        'settings_json' => [
            'colors' => ['primary' => '#ff0066'],
            'announcement' => 'Free shipping over $50',
            'footer_text' => '(c) 2026 Test Store',
        ],
    ]);

    $settings = $this->service->forStore($this->store);

    expect($settings['colors']['primary'])->toBe('#ff0066')
        ->and($settings['announcement'])->toBe('Free shipping over $50')
        ->and($settings['footer_text'])->toBe('(c) 2026 Test Store');
});

it('caches theme settings per store', function (): void {
    $this->service->forStore($this->store);

    expect(Cache::has("theme:settings:store:{$this->store->id}"))->toBeTrue();
});
