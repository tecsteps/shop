<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Theme;
use App\Services\ThemeSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Theme list: cards with publish / duplicate / delete actions. Publishing flips
 * the active theme and busts the theme settings cache.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;

    public function mount(): void
    {
        $this->authorize('viewAny', Theme::class);
    }

    public function getThemesProperty()
    {
        return Theme::query()->orderByDesc('status')->orderBy('name')->get();
    }

    public function publishTheme(int $themeId, ThemeSettingsService $settings): void
    {
        $theme = Theme::query()->findOrFail($themeId);
        $this->authorize('publish', $theme);

        Theme::query()->where('status', ThemeStatus::Published->value)->update(['status' => ThemeStatus::Draft->value]);
        $theme->update(['status' => ThemeStatus::Published->value, 'published_at' => now()]);

        $settings->forget(app('current_store')->id);

        $this->dispatch('toast', type: 'success', message: __('Theme published'));
    }

    public function duplicateTheme(int $themeId): void
    {
        $theme = Theme::query()->with('settings')->findOrFail($themeId);
        $this->authorize('create', Theme::class);

        $copy = $theme->replicate(['published_at']);
        $copy->name = $theme->name.' '.__('(copy)');
        $copy->status = ThemeStatus::Draft->value;
        $copy->published_at = null;
        $copy->save();

        if ($theme->settings !== null) {
            $copy->settings()->create(['settings_json' => $theme->settings->settings_json]);
        }

        $this->dispatch('toast', type: 'success', message: __('Theme duplicated'));
    }

    public function deleteTheme(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);
        $this->authorize('delete', $theme);

        if ($theme->isPublished()) {
            $this->dispatch('toast', type: 'error', message: __('Cannot delete the published theme.'));

            return;
        }

        $theme->delete();

        $this->dispatch('toast', type: 'success', message: __('Theme deleted'));
    }

    public function render()
    {
        return view('livewire.admin.themes.index');
    }
}
