<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ThemeStatus;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends Controller
{
    public function store(Request $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255'], 'version' => ['nullable', 'string', 'max:50']]);
        $theme = Theme::query()->create(['store_id' => $store->id, ...$validated]);

        return response()->json(['data' => $theme], Response::HTTP_CREATED);
    }

    public function publish(Store $store, Theme $theme): JsonResponse
    {
        $this->ensureRelated($store, $theme);
        DB::transaction(function () use ($theme): void {
            Theme::query()->whereKeyNot($theme->id)->update(['status' => ThemeStatus::Draft, 'published_at' => null]);
            $theme->update(['status' => ThemeStatus::Published, 'published_at' => now()]);
        });

        return response()->json(['data' => $theme->refresh()]);
    }

    public function updateSettings(Request $request, Store $store, Theme $theme): JsonResponse
    {
        $this->ensureRelated($store, $theme);
        $validated = $request->validate(['settings' => ['required', 'array']]);
        $settings = $theme->settings()->updateOrCreate(['theme_id' => $theme->id], ['settings_json' => $validated['settings']]);

        return response()->json(['data' => $settings]);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }

    private function ensureRelated(Store $store, Theme $theme): void
    {
        $this->ensureStore($store);
        abort_unless($theme->store_id === $store->id, Response::HTTP_NOT_FOUND);
    }
}
