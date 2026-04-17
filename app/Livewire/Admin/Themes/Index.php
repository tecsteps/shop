<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public function publish(int $themeId): void
    {
        // Unpublish all themes first
        Theme::query()->update([
            'status' => ThemeStatus::Draft,
            'published_at' => null,
        ]);

        $theme = Theme::query()->find($themeId);
        if ($theme) {
            $theme->update([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ]);
        }
    }

    public function duplicate(int $themeId): void
    {
        $theme = Theme::query()->find($themeId);
        if ($theme) {
            Theme::create([
                'store_id' => $theme->store_id,
                'name' => $theme->name.' (Copy)',
                'version' => $theme->version,
                'status' => ThemeStatus::Draft,
            ]);
        }
    }

    public function render(): View
    {
        $themes = Theme::query()->latest()->get();

        return view('livewire.admin.themes.index', [
            'themes' => $themes,
        ]);
    }
}
