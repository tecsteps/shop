<?php

use App\Enums\PageStatus;
use App\Enums\ThemeStatus;
use App\Models\Page;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('theme content models expose their settings files and enum casts', function () {
    $store = Store::factory()->create();
    $theme = Theme::factory()->for($store)->create();
    $file = ThemeFile::factory()->for($theme)->create();
    $settings = ThemeSettings::factory()->for($theme)->create([
        'settings_json' => ['announcement' => ['enabled' => true]],
    ]);
    $page = Page::factory()->for($store)->draft()->create();

    expect($theme->status)->toBe(ThemeStatus::Published)
        ->and($theme->files->sole()->is($file))->toBeTrue()
        ->and($theme->settings->is($settings))->toBeTrue()
        ->and($settings->settings_json)->toBe(['announcement' => ['enabled' => true]])
        ->and($page->status)->toBe(PageStatus::Draft)
        ->and($page->store->is($store))->toBeTrue();
});
