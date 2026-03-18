<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    #[Computed]
    public function themes(): \Illuminate\Database\Eloquent\Collection
    {
        return Theme::where('store_id', app('current_store')->id)
            ->with('settings')
            ->latest('updated_at')
            ->get();
    }

    public function publish(int $themeId): void
    {
        $store = app('current_store');

        Theme::where('store_id', $store->id)
            ->where('status', ThemeStatus::Published)
            ->update(['status' => ThemeStatus::Draft, 'published_at' => null]);

        $theme = Theme::where('store_id', $store->id)->findOrFail($themeId);
        $theme->update([
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ]);

        $this->dispatch('toast', type: 'success', message: __('Theme published.'));
    }

    public function duplicate(int $themeId): void
    {
        $store = app('current_store');
        $theme = Theme::where('store_id', $store->id)->findOrFail($themeId);

        $copy = $theme->replicate(['status', 'published_at']);
        $copy->name = $theme->name.' (Copy)';
        $copy->status = ThemeStatus::Draft;
        $copy->published_at = null;
        $copy->save();

        if ($theme->settings) {
            $copy->settings()->create([
                'settings_json' => $theme->settings->settings_json,
            ]);
        }

        $this->dispatch('toast', type: 'success', message: __('Theme duplicated.'));
    }

    public function deleteTheme(int $themeId): void
    {
        $store = app('current_store');
        $theme = Theme::where('store_id', $store->id)->findOrFail($themeId);

        if ($theme->status === ThemeStatus::Published) {
            $this->dispatch('toast', type: 'error', message: __('Cannot delete the published theme.'));

            return;
        }

        $theme->delete();
        $this->dispatch('toast', type: 'success', message: __('Theme deleted.'));
    }

    public function render(): View
    {
        return view('livewire.admin.themes.index');
    }
}
