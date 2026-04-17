<?php

use App\Models\Theme;
use App\Models\ThemeFile;
use Illuminate\Database\QueryException;

it('belongs to a theme', function () {
    $file = ThemeFile::factory()->create();

    expect($file->theme)->toBeInstanceOf(Theme::class);
});

it('enforces unique path per theme', function () {
    $theme = Theme::factory()->create();
    ThemeFile::factory()->create(['theme_id' => $theme->id, 'path' => 'templates/index.html']);

    ThemeFile::factory()->create(['theme_id' => $theme->id, 'path' => 'templates/index.html']);
})->throws(QueryException::class);

it('factory creates valid theme file', function () {
    $file = ThemeFile::factory()->create();

    expect($file->path)->not->toBeEmpty();
    expect($file->storage_key)->not->toBeEmpty();
    expect($file->sha256)->not->toBeEmpty();
    expect($file->byte_size)->toBeGreaterThanOrEqual(0);
});
