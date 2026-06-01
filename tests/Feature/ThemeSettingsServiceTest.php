<?php

use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->service = app(ThemeSettingsService::class);
});

it('returns defaults when no theme is published', function () {
    expect($this->service->get('dark_mode'))->toBe('system');
    expect($this->service->get('header.sticky'))->toBeTrue();
});

it('merges stored settings on top of defaults', function () {
    $theme = Theme::factory()->published()->create(['store_id' => $this->store->id]);
    ThemeSettings::factory()->withSettings([
        'announcement' => ['enabled' => true, 'text' => 'Hello'],
    ])->create(['theme_id' => $theme->id]);

    // Overridden value...
    expect($this->service->get('announcement.enabled'))->toBeTrue();
    expect($this->service->get('announcement.text'))->toBe('Hello');
    // ...while untouched defaults remain present.
    expect($this->service->get('header.sticky'))->toBeTrue();
    expect($this->service->get('dark_mode'))->toBe('system');
});

it('ignores draft themes and uses defaults', function () {
    $theme = Theme::factory()->create(['store_id' => $this->store->id]); // draft
    ThemeSettings::factory()->withSettings([
        'dark_mode' => 'dark',
    ])->create(['theme_id' => $theme->id]);

    expect($this->service->get('dark_mode'))->toBe('system');
});

it('caches resolved settings per store', function () {
    Theme::factory()->published()->create(['store_id' => $this->store->id]);

    expect(Cache::has("theme_settings:{$this->store->id}"))->toBeFalse();

    $this->service->all();

    expect(Cache::has("theme_settings:{$this->store->id}"))->toBeTrue();
});

it('forgets cached settings on demand', function () {
    Theme::factory()->published()->create(['store_id' => $this->store->id]);
    $this->service->all();

    $this->service->forget($this->store->id);

    expect(Cache::has("theme_settings:{$this->store->id}"))->toBeFalse();
});

it('exposes a stable defaults shape', function () {
    $defaults = ThemeSettingsService::defaults();

    expect($defaults)
        ->toHaveKeys(['announcement', 'header', 'home', 'colors', 'typography', 'footer', 'dark_mode']);
    expect($defaults['home']['sections'])->toBeArray()->not->toBeEmpty();
});
