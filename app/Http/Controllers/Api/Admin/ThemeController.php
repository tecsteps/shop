<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ThemeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreThemeRequest;
use App\Http\Requests\Admin\UpdateThemeSettingsRequest;
use App\Http\Resources\Admin\ThemeResource;
use App\Models\Store;
use App\Models\Theme;
use App\Services\ThemeArchiveImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ThemeController extends Controller
{
    public function store(StoreThemeRequest $request, Store $store, ThemeArchiveImporter $importer): JsonResponse
    {
        $theme = $importer->import(
            $store,
            $request->file('file'),
            $request->validated('name'),
        );

        return (new ThemeResource($theme))
            ->response()
            ->setStatusCode(201);
    }

    public function publish(Store $store, int $theme): ThemeResource
    {
        $existingTheme = $this->findTheme($store, $theme);

        Theme::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->update([
                'status' => ThemeStatus::Draft->value,
                'published_at' => null,
            ]);

        $existingTheme->forceFill([
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ])->save();

        Cache::forget("theme_settings:{$store->id}");

        return new ThemeResource($existingTheme->refresh()->load('settings')->loadCount('files'));
    }

    public function updateSettings(UpdateThemeSettingsRequest $request, Store $store, int $theme): ThemeResource
    {
        $existingTheme = $this->findTheme($store, $theme);

        $existingTheme->settings()->updateOrCreate(
            ['theme_id' => $existingTheme->id],
            ['settings_json' => $request->validated('settings_json')],
        );

        Cache::forget("theme_settings:{$store->id}");

        return new ThemeResource($existingTheme->refresh()->load('settings')->loadCount('files'));
    }

    private function findTheme(Store $store, int $themeId): Theme
    {
        return Theme::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($themeId)
            ->firstOrFail();
    }
}
