<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Theme;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use UsesAdminStore;

    public function publish(int $themeId): void
    {
        Theme::query()->update(['status' => ThemeStatus::Draft->value, 'published_at' => null]);
        Theme::query()->whereKey($themeId)->firstOrFail()->forceFill([
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ])->save();

        $this->notify('Theme published.');
    }

    public function duplicate(int $themeId): void
    {
        $theme = Theme::query()->with('settings')->whereKey($themeId)->firstOrFail();
        $copy = $theme->replicate(['status', 'published_at']);
        $copy->name = $theme->name.' copy';
        $copy->status = ThemeStatus::Draft;
        $copy->published_at = null;
        $copy->save();

        if ($theme->settings) {
            $copy->settings()->create([
                'settings_json' => $theme->settings->settings_json,
            ]);
        }

        $this->notify('Theme duplicated.');
    }

    public function render(): View
    {
        return view('livewire.admin.themes.index', [
            'themes' => Theme::query()->with('settings')->latest('updated_at')->get(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Themes',
        ]);
    }
}
