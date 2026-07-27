<?php

use App\Models\Theme;
use App\Models\ThemeFile;
use App\Services\ThemeSettingsService;

test('defaults apply when the store has no published theme', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $service = app(ThemeSettingsService::class);

    expect($service->all())->toBe(ThemeSettingsService::DEFAULTS)
        ->and($service->get('announcement.enabled'))->toBeFalse()
        ->and($service->get('hero.cta_label'))->toBe('Shop now')
        ->and($service->get('sections_order'))->toBe(['hero', 'featured_collections', 'featured_products', 'newsletter', 'rich_text']);
});

test('published theme settings win over the defaults', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $theme = Theme::factory()->published()->create(['store_id' => $store->id]);
    $theme->settings()->create([
        'settings_json' => [
            'announcement' => ['enabled' => true, 'text' => 'Free shipping this week'],
            'hero' => ['cta_label' => 'Browse the sale'],
        ],
    ]);

    $service = app(ThemeSettingsService::class);

    expect($service->get('announcement.enabled'))->toBeTrue()
        ->and($service->get('announcement.text'))->toBe('Free shipping this week')
        ->and($service->get('hero.cta_label'))->toBe('Browse the sale')
        // Untouched keys keep their defaults.
        ->and($service->get('hero.enabled'))->toBeTrue()
        ->and($service->get('hero.cta_url'))->toBe('/collections');
});

test('draft theme settings are ignored', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $theme = Theme::factory()->create(['store_id' => $store->id]);
    $theme->settings()->create(['settings_json' => ['hero' => ['cta_label' => 'Hidden label']]]);

    expect(app(ThemeSettingsService::class)->get('hero.cta_label'))->toBe('Shop now');
});

test('saving settings invalidates the cached values', function () {
    $store = $this->createStore();
    $this->bindStore($store);

    $theme = Theme::factory()->published()->create(['store_id' => $store->id]);
    $settings = $theme->settings()->create(['settings_json' => ['hero' => ['cta_label' => 'Before']]]);

    $service = app(ThemeSettingsService::class);

    expect($service->get('hero.cta_label'))->toBe('Before');

    $settings->update(['settings_json' => ['hero' => ['cta_label' => 'After']]]);

    expect($service->get('hero.cta_label'))->toBe('After');
});

test('publishing a theme demotes the previously published theme', function () {
    $store = $this->createStore();

    $first = Theme::factory()->published()->create(['store_id' => $store->id]);
    $second = Theme::factory()->create(['store_id' => $store->id]);

    $second->publish();

    expect($second->refresh()->isPublished())->toBeTrue()
        ->and($first->refresh()->isPublished())->toBeFalse()
        ->and($second->published_at)->not->toBeNull();
});

test('duplicating a theme copies files and settings as a draft', function () {
    $store = $this->createStore();

    $theme = Theme::factory()->published()->create(['store_id' => $store->id]);
    ThemeFile::factory()->count(2)->create(['theme_id' => $theme->id]);
    $theme->settings()->create(['settings_json' => ['hero' => ['heading' => 'Copied hero']]]);

    $copy = $theme->duplicate('Winter copy');

    expect($copy->name)->toBe('Winter copy')
        ->and($copy->isPublished())->toBeFalse()
        ->and($copy->files)->toHaveCount(2)
        ->and($copy->settings->settings_json)->toBe(['hero' => ['heading' => 'Copied hero']]);
});
