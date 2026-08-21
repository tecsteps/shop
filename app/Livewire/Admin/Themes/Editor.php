<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Editor extends Component
{
    public Theme $theme;

    public string $settingsJson = '{}';

    public function mount(Theme $theme): void
    {
        $this->theme = $theme;
        $this->settingsJson = json_encode($theme->settings?->settings_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function save(): void
    {
        $this->authorize('update', $this->theme);
        $data = $this->validate(['settingsJson' => ['required', 'json']]);
        $this->theme->settings()->updateOrCreate(
            ['theme_id' => $this->theme->getKey()],
            ['settings_json' => json_decode($data['settingsJson'], true, 512, JSON_THROW_ON_ERROR)],
        );
        $this->dispatch('toast', message: 'Theme settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor')->layout('layouts.admin');
    }
}
