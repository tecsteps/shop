<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\ThemeStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\ThemeResource;
use App\Models\Store;
use App\Models\Theme;
use App\Services\ThemeArchiveInstaller;
use App\Services\ThemeSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ThemeController extends Controller
{
    public function store(Request $request, Store $store, ThemeArchiveInstaller $installer): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:zip', 'max:51200'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $archive = $request->file('file');

        abort_unless($archive instanceof UploadedFile, 422);

        $theme = $installer->install($store, $archive, $validated['name'] ?? null);

        return ThemeResource::make($theme)
            ->response()
            ->setStatusCode(201);
    }

    public function publish(Request $request, Store $store, Theme $theme, ThemeSettingsService $settings): ThemeResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessThemeBelongsToStore($theme, $store);
        $this->validatePublishable($theme);

        DB::transaction(function () use ($store, $theme): void {
            Theme::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->update([
                    'status' => ThemeStatus::Draft,
                    'published_at' => null,
                ]);

            $theme->forceFill([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ])->save();
        });

        $settings->forget($store);

        return ThemeResource::make($theme->refresh()->load('settings')->loadCount('files'));
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('sanctum_personal_access_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessThemeBelongsToStore(Theme $theme, Store $store): void
    {
        abort_unless((int) $theme->store_id === $store->getKey(), 404);
    }

    private function validatePublishable(Theme $theme): void
    {
        $paths = $theme->files()
            ->withoutGlobalScopes()
            ->pluck('path')
            ->all();
        $missing = array_values(array_diff(ThemeArchiveInstaller::requiredPaths(), $paths));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'theme' => __('The theme is missing required file: :path', ['path' => $missing[0]]),
            ]);
        }
    }
}
