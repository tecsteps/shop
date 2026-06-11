<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Theme;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts;

    public function mount(): void
    {
        $this->authorize('viewAny', Theme::class);
    }

    /**
     * Publish a theme: it becomes the single published theme for the store
     * and the cached storefront settings are invalidated.
     */
    public function publishTheme(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);

        $this->authorize('publish', $theme);

        DB::transaction(function () use ($theme): void {
            Theme::query()
                ->whereKeyNot($theme->getKey())
                ->where('status', ThemeStatus::Published)
                ->update(['status' => ThemeStatus::Draft]);

            $theme->update([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ]);
        });

        app(ThemeSettingsService::class)->forget($theme->store_id);

        $this->toast(__('Theme published'));
    }

    /**
     * Duplicate a theme (including its settings) as a draft copy.
     */
    public function duplicateTheme(int $themeId): void
    {
        $theme = Theme::query()->with('settings')->findOrFail($themeId);

        $this->authorize('create', Theme::class);

        DB::transaction(function () use ($theme): void {
            $copy = Theme::query()->create([
                'store_id' => $theme->store_id,
                'name' => __(':name (Copy)', ['name' => $theme->name]),
                'version' => $theme->version,
                'status' => ThemeStatus::Draft,
                'published_at' => null,
            ]);

            if ($theme->settings !== null) {
                $copy->settings()->create(['settings_json' => $theme->settings->settings_json]);
            }
        });

        $this->toast(__('Theme duplicated.'));
    }

    public function deleteTheme(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);

        $this->authorize('delete', $theme);

        if ($theme->status === ThemeStatus::Published) {
            $this->toast(__('The published theme cannot be deleted. Publish another theme first.'), 'error');

            return;
        }

        $theme->settings()->delete();
        $theme->files()->delete();
        $theme->delete();

        $this->toast(__('Theme deleted.'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Theme>
     */
    #[Computed]
    public function themes(): \Illuminate\Database\Eloquent\Collection
    {
        return Theme::query()
            ->orderByDesc('status')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.themes.index')->title(__('Themes'));
    }
}
