<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ThemeController extends AdminController
{
    public function index(): View
    {
        $themes = Theme::query()
            ->with('settings')
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [ThemeStatus::Published->value])
            ->orderBy('name')
            ->get();

        return view('admin.themes.index', [
            'themes' => $themes,
        ]);
    }

    public function edit(Theme $theme): View
    {
        $settings = ThemeSetting::query()->firstOrCreate(
            ['theme_id' => $theme->id],
            ['settings_json' => []],
        );

        $settingsJson = json_encode(
            $settings->settings_json ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        return view('admin.themes.edit', [
            'theme' => $theme,
            'settingsJson' => $settingsJson === false ? '{}' : $settingsJson,
        ]);
    }

    public function update(Request $request, Theme $theme): RedirectResponse
    {
        $validated = $request->validate([
            'settings_json' => ['required', 'string'],
        ]);

        $decoded = json_decode($validated['settings_json'], true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors([
                'settings_json' => 'Settings must be valid JSON.',
            ])->withInput();
        }

        ThemeSetting::query()->updateOrCreate(
            ['theme_id' => $theme->id],
            ['settings_json' => $decoded],
        );

        return redirect()->route('admin.themes.edit', $theme)
            ->with('status', 'Theme settings saved.');
    }

    public function publish(Theme $theme): RedirectResponse
    {
        DB::transaction(function () use ($theme): void {
            Theme::query()
                ->where('status', ThemeStatus::Published->value)
                ->whereKeyNot($theme->id)
                ->update([
                    'status' => ThemeStatus::Draft->value,
                    'published_at' => null,
                ]);

            $theme->update([
                'status' => ThemeStatus::Published->value,
                'published_at' => now(),
            ]);
        });

        return redirect()->route('admin.themes.index')
            ->with('status', 'Theme published.');
    }
}
