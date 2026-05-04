<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\ThemeResource;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ThemeSettingsController extends Controller
{
    public function update(Request $request, Store $store, Theme $theme, ThemeSettingsService $settings): ThemeResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessThemeBelongsToStore($theme, $store);

        $validated = $request->validate([
            'settings_json' => ['required', 'array'],
        ]);

        if (array_is_list($validated['settings_json'])) {
            throw ValidationException::withMessages([
                'settings_json' => __('The settings json field must be an object.'),
            ]);
        }

        ThemeSettings::withoutGlobalScopes()->updateOrCreate(
            ['theme_id' => $theme->getKey()],
            [
                'settings_json' => $validated['settings_json'],
                'updated_at' => now(),
            ],
        );

        $settings->forget($store);

        return ThemeResource::make($theme->refresh()->load('settings')->loadCount('files'));
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessThemeBelongsToStore(Theme $theme, Store $store): void
    {
        abort_unless((int) $theme->store_id === $store->getKey(), 404);
    }
}
