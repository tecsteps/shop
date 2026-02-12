<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Theme Editor')]
class Editor extends Component
{
    public Theme $theme;

    public string $settingsJson = '{}';

    public function mount(Theme $theme): void
    {
        $store = app('current_store');
        abort_unless($theme->store_id === $store->id, 404);

        $this->theme = $theme;

        $settings = $theme->settings;
        $this->settingsJson = $settings
            ? json_encode($settings->settings_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : '{}';
    }

    public function save(): void
    {
        $this->validate([
            'settingsJson' => ['required', 'json'],
        ]);

        ThemeSettings::updateOrCreate(
            ['theme_id' => $this->theme->id],
            ['settings_json' => json_decode($this->settingsJson, true)]
        );

        $this->dispatch('toast', type: 'success', message: 'Theme settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor');
    }
}
