<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    /**
     * @return Collection<int, Theme>
     */
    #[Computed]
    public function themes(): Collection
    {
        return Theme::with('settings')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function publishTheme(int $themeId): void
    {
        Theme::query()->update(['is_active' => false, 'status' => ThemeStatus::Draft]);

        $theme = Theme::findOrFail($themeId);
        $theme->update([
            'is_active' => true,
            'status' => ThemeStatus::Published,
        ]);

        $this->dispatch('toast', type: 'success', message: "Theme \"{$theme->name}\" published.");
    }

    public function duplicateTheme(int $themeId): void
    {
        $theme = Theme::with('settings')->findOrFail($themeId);

        $newTheme = $theme->replicate();
        $newTheme->name = $theme->name.' (Copy)';
        $newTheme->is_active = false;
        $newTheme->status = ThemeStatus::Draft;
        $newTheme->save();

        if ($theme->settings) {
            $newTheme->settings()->create([
                'settings_json' => $theme->settings->settings_json,
            ]);
        }

        $this->dispatch('toast', type: 'success', message: 'Theme duplicated.');
    }

    public function deleteTheme(int $themeId): void
    {
        $theme = Theme::findOrFail($themeId);

        if ($theme->is_active) {
            $this->dispatch('toast', type: 'error', message: 'Cannot delete the active theme.');

            return;
        }

        $theme->settings()?->delete();
        $theme->delete();
        $this->dispatch('toast', type: 'success', message: 'Theme deleted.');
    }

    public function render()
    {
        return view('livewire.admin.themes.index')
            ->layout('layouts.admin', ['title' => 'Themes']);
    }
}
