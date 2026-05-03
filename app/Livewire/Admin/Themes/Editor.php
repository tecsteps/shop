<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Theme;
use Illuminate\View\View;
use Livewire\Component;

class Editor extends Component
{
    use UsesAdminStore;

    public Theme $theme;

    public string $settingsJson = '{}';

    public function mount(Theme $theme): void
    {
        $this->theme = $theme->load('settings');
        $this->settingsJson = json_encode($this->theme->settings?->settings_json ?? [], JSON_PRETTY_PRINT) ?: '{}';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'settingsJson' => ['required', 'json'],
        ]);

        $this->theme->settings()->updateOrCreate(
            ['theme_id' => $this->theme->id],
            ['settings_json' => json_decode($validated['settingsJson'], true)],
        );

        $this->notify('Theme settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor')->layout('livewire.admin.layout.app', [
            'title' => 'Theme editor',
        ]);
    }
}
